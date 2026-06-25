<?php
defined( 'ABSPATH' ) || exit;

$layout             = ! empty( $attributes['layout'] ) ? $attributes['layout'] : 'row';
$button_color       = ! empty( $attributes['buttonColor'] ) ? $attributes['buttonColor'] : '';
$button_text_color  = ! empty( $attributes['buttonTextColor'] ) ? $attributes['buttonTextColor'] : '';

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
	$post = get_post( $preview_ids[0] );
	setup_postdata( $post );
	$GLOBALS['product'] = wc_get_product( $preview_ids[0] );

	// Capture form HTML so we can inject static stepper buttons.
	// WooCommerce's quantity stepper (+/- buttons) is normally added by theme/plugin JS
	// which does not run inside the block editor iframe. We replicate the output here
	// so the editor preview matches the frontend appearance.
	ob_start();
	woocommerce_template_single_add_to_cart();
	$form_html = ob_get_clean();

	// Mirror what GeneratePress Premium JS does: add "buttons-added" class and prepend minus
	// button as the first child of .quantity, then append plus button after the input.
	$form_html = str_replace(
		'<div class="quantity">',
		'<div class="quantity buttons-added"><a href="javascript:void(0)" class="minus">-</a>',
		$form_html
	);
	$form_html = preg_replace(
		'/<input\b.*?\/>/s',
		'$0<a href="javascript:void(0)" class="plus">+</a>',
		$form_html,
		1
	);

	$form_align = 'row' === $layout ? 'center' : 'flex-start';
	$btn_bg     = $button_color ? esc_attr( $button_color ) : 'var(--wc-primary, #2271b1)';
	$btn_text   = $button_text_color ? esc_attr( $button_text_color ) : 'var(--wc-primary-text, #fff)';

	// Scoped styles injected directly into the editor canvas iframe via the SSR payload.
	// enqueue_block_editor_assets styles target the outer admin frame, not the canvas iframe,
	// so they cannot be relied upon here. These styles replicate the layout that WooCommerce
	// and GeneratePress Premium CSS would provide on the frontend.
	?>
	<style>
	.woocanvas-atc-preview {
		pointer-events: none;
	}
	.woocanvas-atc-preview form.cart {
		display: flex;
		flex-wrap: wrap;
		flex-direction: <?php echo esc_attr( $layout ); ?>;
		align-items: <?php echo esc_attr( $form_align ); ?>;
		gap: 4px;
	}
	.woocanvas-atc-preview .quantity.buttons-added {
		display: flex;
		align-items: center;
	}
	.woocanvas-atc-preview .quantity.buttons-added .minus,
	.woocanvas-atc-preview .quantity.buttons-added .qty,
	.woocanvas-atc-preview .quantity.buttons-added .plus {
		height: 50px;
		min-height: 50px;
		width: 50px;
		border: 1px solid rgba(0, 0, 0, .1);
		display: flex;
		align-items: center;
		justify-content: center;
		box-sizing: border-box;
		text-decoration: none;
		color: inherit;
	}
	.woocanvas-atc-preview .quantity.buttons-added .minus { border-right-width: 0; }
	.woocanvas-atc-preview .quantity.buttons-added .plus  { border-left-width: 0; }
	/* The label is for screen readers only — hide it visually in the preview. */
	.woocanvas-atc-preview .screen-reader-text {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border: 0;
	}
	.woocanvas-atc-preview .quantity.buttons-added .qty {
		-webkit-appearance: none;
		-moz-appearance: textfield;
		background: transparent;
		padding: 0;
		text-align: center;
	}
	.woocanvas-atc-preview .quantity.buttons-added .qty::-webkit-inner-spin-button,
	.woocanvas-atc-preview .quantity.buttons-added .qty::-webkit-outer-spin-button {
		-webkit-appearance: none;
	}
	.woocanvas-atc-preview .single_add_to_cart_button {
		background-color: <?php echo $btn_bg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
		color: <?php echo $btn_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
		padding: 10px 20px;
		min-height: 50px;
		border: 0;
		cursor: default;
		display: flex;
		align-items: center;
	}
	</style>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<div class="woocanvas-atc-preview">' . $form_html . '</div>';

	wp_reset_postdata();
	return;
}

$product = wc_get_product( get_the_ID() );
if ( ! $product ) {
	return;
}

$GLOBALS['product'] = $product;

$block_id = wp_unique_id( 'woocanvas-atc-' );

$form_align = 'row' === $layout ? 'center' : 'flex-start';
$css        = "#${block_id} form.cart { display: flex; flex-wrap: wrap; flex-direction: " . esc_attr( $layout ) . '; align-items: ' . esc_attr( $form_align ) . "; gap: 8px; }\n";
if ( $button_color ) {
	$css .= "#${block_id} .single_add_to_cart_button { background-color: " . esc_attr( $button_color ) . " !important; }\n";
}
if ( $button_text_color ) {
	$css .= "#${block_id} .single_add_to_cart_button { color: " . esc_attr( $button_text_color ) . " !important; }\n";
}
?>
<style><?php echo $css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
<div id="<?php echo esc_attr( $block_id ); ?>" <?php echo get_block_wrapper_attributes( [ 'class' => 'woocommerce' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php woocommerce_template_single_add_to_cart(); ?>
</div>
