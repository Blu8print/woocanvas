<?php
defined( 'ABSPATH' ) || exit;

$max_width        = $attributes['maxWidth'] ?? '';
$aspect_ratio_key = $attributes['aspectRatio'] ?? 'natural';
$thumb_position   = $attributes['thumbnailPosition'] ?? 'below';

$aspect_ratio_map = [
	'1-1'  => '1 / 1',
	'4-3'  => '4 / 3',
	'3-4'  => '3 / 4',
	'16-9' => '16 / 9',
];
$aspect_ratio_css = $aspect_ratio_map[ $aspect_ratio_key ] ?? 'auto';

$placeholder_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';

// ─────────────────────────────────────────────────────────────────────────────
// Shared helper: build CSS rules scoped to $block_id.
// Used by both the editor preview and the frontend branch.
// ─────────────────────────────────────────────────────────────────────────────
$build_css = function ( string $id ) use ( $max_width, $aspect_ratio_key, $aspect_ratio_css, $thumb_position ): string {
	$rules = [];

	if ( $max_width ) {
		$rules[] = '#' . $id . ' { max-width: ' . esc_attr( $max_width ) . '; }';
	}

	if ( 'natural' !== $aspect_ratio_key ) {
		$rules[] = '#' . $id . ' .woocommerce-product-gallery__image img { aspect-ratio: ' . $aspect_ratio_css . '; object-fit: cover; width: 100%; height: 100%; }';
	}

	if ( 'hidden' === $thumb_position ) {
		$rules[] = '#' . $id . ' .flex-control-thumbs { display: none; }';
	} elseif ( 'top' === $thumb_position ) {
		$rules[] = '#' . $id . ' .woocommerce-product-gallery { display: flex; flex-direction: column-reverse; gap: 8px; }';
	} elseif ( 'left' === $thumb_position ) {
		$rules[] = '#' . $id . ' .woocommerce-product-gallery { display: flex; flex-direction: row-reverse; gap: 8px; align-items: flex-start; }';
		$rules[] = '#' . $id . ' .woocommerce-product-gallery .flex-control-thumbs { display: flex; flex-direction: column; width: 80px; flex-shrink: 0; gap: 4px; }';
		$rules[] = '#' . $id . ' .woocommerce-product-gallery .flex-control-thumbs li { width: 100% !important; margin: 0 !important; }';
		$rules[] = '#' . $id . ' .woocommerce-product-gallery .flex-viewport { flex: 1; }';
	} elseif ( 'right' === $thumb_position ) {
		$rules[] = '#' . $id . ' .woocommerce-product-gallery { display: flex; flex-direction: row; gap: 8px; align-items: flex-start; }';
		$rules[] = '#' . $id . ' .woocommerce-product-gallery .flex-control-thumbs { display: flex; flex-direction: column; width: 80px; flex-shrink: 0; gap: 4px; }';
		$rules[] = '#' . $id . ' .woocommerce-product-gallery .flex-control-thumbs li { width: 100% !important; margin: 0 !important; }';
		$rules[] = '#' . $id . ' .woocommerce-product-gallery .flex-viewport { flex: 1; }';
	}

	return implode( ' ', $rules );
};

// ─────────────────────────────────────────────────────────────────────────────
// Build custom thumbnail strip (editor only — Flexslider JS doesn't run there).
// ─────────────────────────────────────────────────────────────────────────────
$build_thumb_strip = function ( string $id, WC_Product $product ) use ( $placeholder_icon, $thumb_position ): string {
	if ( 'hidden' === $thumb_position ) {
		return '';
	}

	$main_img_id = $product->get_image_id();
	$gallery_ids = $product->get_gallery_image_ids();
	$all_ids     = array_filter( array_merge( [ $main_img_id ], $gallery_ids ) );

	$items = '';
	foreach ( $all_ids as $img_id ) {
		$items .= '<div class="woocanvas-thumb-item">'
			. wp_get_attachment_image( $img_id, 'woocommerce_thumbnail' )
			. '</div>';
	}

	$placeholder_count = max( 0, 3 - count( $all_ids ) );
	for ( $i = 0; $i < $placeholder_count; $i++ ) {
		$items .= '<div class="woocanvas-thumb-item woocanvas-thumb-placeholder">'
			. $placeholder_icon
			. '</div>';
	}

	$col_style = in_array( $thumb_position, [ 'left', 'right' ], true )
		? 'flex-direction:column;width:72px;flex-shrink:0;'
		: '';

	return '<div class="woocanvas-thumb-strip" style="display:flex;flex-wrap:wrap;gap:4px;' . $col_style . '">'
		. $items
		. '</div>';
};

$thumb_strip_item_css = '
.woocanvas-thumb-item { width: 68px; height: 68px; overflow: hidden; border: 1px solid #ddd; border-radius: 4px; flex-shrink: 0; }
.woocanvas-thumb-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.woocanvas-thumb-placeholder { background: #f5f5f5; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; }';

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
	$post    = get_post( $preview_ids[0] );
	setup_postdata( $post );
	$product            = wc_get_product( $preview_ids[0] );
	$GLOBALS['product'] = $product;

	$block_id    = wp_unique_id( 'woocanvas-gallery-' );
	$main_img_id = $product->get_image_id();

	// Main image — use the same WooCommerce image size as the frontend.
	// Apply aspect-ratio inline so it overrides wp_get_attachment_image's width/height HTML attributes.
	$img_style = 'width:100%;height:auto;display:block;';
	if ( 'natural' !== $aspect_ratio_key ) {
		$img_style = 'width:100%;height:auto;aspect-ratio:' . $aspect_ratio_css . ';object-fit:cover;display:block;';
	}

	if ( $main_img_id ) {
		$main_img_html = wp_get_attachment_image( $main_img_id, 'woocommerce_single', false, [
			'class' => 'woocanvas-main-image',
			'style' => $img_style,
		] );
	} else {
		$main_img_html = '<div class="woocanvas-main-image woocanvas-thumb-placeholder" style="width:100%;aspect-ratio:' . ( 'natural' !== $aspect_ratio_key ? $aspect_ratio_css : '1/1' ) . ';display:flex;align-items:center;justify-content:center;">'
			. $placeholder_icon
			. '</div>';
	}

	$thumb_strip = $build_thumb_strip( $block_id, $product );

	$is_row = in_array( $thumb_position, [ 'left', 'right' ], true );

	$wrapper_style = $is_row
		? 'display:flex;flex-direction:row;gap:8px;align-items:flex-start;'
		: 'display:flex;flex-direction:column;gap:8px;';
	if ( $max_width ) {
		$wrapper_style .= 'max-width:' . esc_attr( $max_width ) . ';';
	}

	$img_wrap_style = $is_row ? 'flex:1;min-width:0;' : '';

	$aspect_css = $max_width ? '#' . $block_id . ' { max-width: ' . esc_attr( $max_width ) . '; }' : '';

	echo '<style>' . $aspect_css . $thumb_strip_item_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<div id="' . esc_attr( $block_id ) . '" style="' . esc_attr( $wrapper_style ) . '">';

	// Strip-first positions: top, left.
	if ( in_array( $thumb_position, [ 'top', 'left' ], true ) ) {
		echo $thumb_strip; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div' . ( $img_wrap_style ? ' style="' . esc_attr( $img_wrap_style ) . '"' : '' ) . '>' . $main_img_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		// below, right, hidden: image first.
		echo '<div' . ( $img_wrap_style ? ' style="' . esc_attr( $img_wrap_style ) . '"' : '' ) . '>' . $main_img_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $thumb_strip; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</div>';
	wp_reset_postdata();
	return;
}

// ── FRONTEND ──────────────────────────────────────────────────────────────────
$product = wc_get_product( get_the_ID() );
if ( ! $product ) {
	return;
}

$GLOBALS['product'] = $product;
$block_id           = wp_unique_id( 'woocanvas-gallery-' );

$wrapper_style = $max_width ? 'max-width:' . esc_attr( $max_width ) . ';' : '';
$wrapper_attrs = get_block_wrapper_attributes( [
	'id'    => $block_id,
	'style' => $wrapper_style,
] );

$inline_css = $build_css( $block_id );
?>
<?php if ( $inline_css ) : ?>
<style><?php echo $inline_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
<?php endif; ?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php woocommerce_show_product_images(); ?>
</div>
