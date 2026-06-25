<?php
defined( 'ABSPATH' ) || exit;

$tab_style     = $attributes['tabStyle'] ?? 'default';
$active_color  = $attributes['activeColor'] ?? '';
$tab_alignment = $attributes['tabAlignment'] ?? 'left';
$show_reviews  = $attributes['showReviews'] ?? true;

$remove_reviews_tab = static function ( $tabs ) {
	unset( $tabs['reviews'] );
	return $tabs;
};

if ( ! $show_reviews ) {
	add_filter( 'woocommerce_product_tabs', $remove_reviews_tab, 99 );
}

// ─────────────────────────────────────────────────────────────────────────────
// CSS builders — editor and frontend share the same logic, different scopes.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Returns override-only CSS for the frontend, scoped to a unique block ID.
 * WooCommerce's own woocommerce.css provides the default tab appearance;
 * we only need to emit rules when attributes deviate from the WC defaults.
 */
$build_frontend_css = function ( string $id ) use ( $tab_style, $active_color, $tab_alignment ): string {
	$s     = '#' . $id;
	$rules = [];

	if ( 'center' === $tab_alignment ) {
		$rules[] = $s . ' ul.tabs { display: flex; justify-content: center; padding-left: 0; }';
	} elseif ( 'right' === $tab_alignment ) {
		$rules[] = $s . ' ul.tabs { display: flex; justify-content: flex-end; padding-left: 0; }';
	}

	if ( 'underline' === $tab_style ) {
		$color   = $active_color ?: '#515151';
		$rules[] = $s . ' ul.tabs li { background: transparent; border: none; border-bottom: 2px solid transparent; border-radius: 0; box-shadow: none; margin: 0; padding: 0 1em; }';
		$rules[] = $s . ' ul.tabs li::before, ' . $s . ' ul.tabs li::after { display: none; }';
		$rules[] = $s . ' ul.tabs li.active { background: transparent; border-bottom-color: ' . esc_attr( $color ) . '; }';
		$rules[] = $s . ' ul.tabs li.active a { color: ' . esc_attr( $color ) . '; }';
	} elseif ( $active_color ) {
		// Default style + custom accent: colored top-border strip + link color.
		$rules[] = $s . ' ul.tabs li.active { border-top-color: ' . esc_attr( $active_color ) . '; }';
		$rules[] = $s . ' ul.tabs li.active a { color: ' . esc_attr( $active_color ) . '; }';
	}

	return implode( "\n", $rules );
};

/**
 * Returns complete CSS for the editor preview, where WC CSS is not available.
 * The scope is always .woocanvas-tabs-preview; the style variant controls
 * which base tab appearance is rendered.
 */
$build_editor_css = function () use ( $tab_style, $active_color, $tab_alignment ): string {
	$s     = '.woocanvas-tabs-preview';
	$color = $active_color ?: '#515151';

	// Alignment drives the ul.tabs flex rule; default (left) keeps WC's padding indent.
	$ul_extra = 'left' === $tab_alignment
		? 'padding: 0 0 0 1em;'
		: 'padding: 0; display: flex; justify-content: ' . ( 'center' === $tab_alignment ? 'center' : 'flex-end' ) . ';';

	$css = $s . ' { pointer-events: none; }
' . $s . ' .wc-tab { display: none; }
' . $s . ' .wc-tab:first-of-type { display: block; }
' . $s . ' .panel { margin: 0 0 2em; padding: 0; }
' . $s . ' ul.tabs { list-style: none; ' . $ul_extra . ' margin: 0 0 1.618em; overflow: hidden; position: relative; }
' . $s . ' ul.tabs::before { position: absolute; content: " "; width: 100%; bottom: 0; left: 0; border-bottom: 1px solid #cfc8d8; z-index: 1; }
';

	if ( 'default' === $tab_style ) {
		$css .= $s . ' ul.tabs li { border: 1px solid #cfc8d8; background-color: #e9e6ed; color: #515151; display: inline-block; position: relative; z-index: 0; border-radius: 4px 4px 0 0; margin: 0 -5px; padding: 0 1em; }
' . $s . ' ul.tabs li a { display: inline-block; padding: .5em 0; font-weight: 700; color: #515151; text-decoration: none; }
' . $s . ' ul.tabs li.active { background: #fff; z-index: 2; border-bottom-color: #fff; ' . ( $active_color ? 'border-top-color: ' . esc_attr( $active_color ) . ';' : '' ) . ' }
' . $s . ' ul.tabs li.active a { color: ' . esc_attr( $color ) . '; }
' . $s . ' ul.tabs li.active::before { box-shadow: 2px 2px 0 #fff; }
' . $s . ' ul.tabs li.active::after { box-shadow: -2px 2px 0 #fff; }
' . $s . ' ul.tabs li::before, ' . $s . ' ul.tabs li::after { border: 1px solid #cfc8d8; position: absolute; bottom: -1px; width: 5px; height: 5px; content: " "; box-sizing: border-box; }
' . $s . ' ul.tabs li::before { left: -5px; border-bottom-right-radius: 4px; border-width: 0 1px 1px 0; box-shadow: 2px 2px 0 #e9e6ed; }
' . $s . ' ul.tabs li::after { right: -5px; border-bottom-left-radius: 4px; border-width: 0 0 1px 1px; box-shadow: -2px 2px 0 #e9e6ed; }
';
	} else { // underline
		$css .= $s . ' ul.tabs li { background: transparent; border: none; border-bottom: 2px solid transparent; border-radius: 0; margin: 0; padding: 0 1em; display: inline-block; position: relative; z-index: 0; }
' . $s . ' ul.tabs li a { display: inline-block; padding: .5em 0; font-weight: 700; color: #515151; text-decoration: none; }
' . $s . ' ul.tabs li.active { background: transparent; border-bottom: 2px solid ' . esc_attr( $color ) . '; z-index: 2; }
' . $s . ' ul.tabs li.active a { color: ' . esc_attr( $color ) . '; }
';
	}

	return $css;
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
	setup_postdata( $post );
	$GLOBALS['product'] = wc_get_product( $preview_ids[0] );

	echo '<style>' . $build_editor_css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<div class="woocanvas-tabs-preview">';
	woocommerce_output_product_data_tabs();
	echo '</div>';

	if ( ! $show_reviews ) {
		remove_filter( 'woocommerce_product_tabs', $remove_reviews_tab, 99 );
	}

	wp_reset_postdata();
	return;
}

// ── FRONTEND ──────────────────────────────────────────────────────────────────
$product = wc_get_product( get_the_ID() );
if ( ! $product ) {
	return;
}

$GLOBALS['product'] = $product;
$block_id           = wp_unique_id( 'woocanvas-tabs-' );
$inline_css         = $build_frontend_css( $block_id );
?>
<?php if ( $inline_css ) : ?>
<style><?php echo $inline_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
<?php endif; ?>
<div id="<?php echo esc_attr( $block_id ); ?>" <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
	woocommerce_output_product_data_tabs();
	if ( ! $show_reviews ) {
		remove_filter( 'woocommerce_product_tabs', $remove_reviews_tab, 99 );
	}
	?>
</div>
