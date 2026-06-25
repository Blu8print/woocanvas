# Block: `woocanvas/related-products`

Renders the WooCommerce related products and upsells grid.

---

## Files

```
blocks/related-products/
  block.json   — Block metadata, supports
  index.js     — Editor registration (ServerSideRender only, no controls)
  render.php   — Server-side render (editor preview + frontend)
```

---

## Editor (`index.js`)

Plain IIFE — no JSX, no build step. Registered as `woocanvas-related-products-editor` in `class-block-registrar.php` with `wp-server-side-render` as an explicit dependency.

This block has no custom attributes and no `InspectorControls`. The edit function is the simplest of the four WooCanvas blocks — it only wraps `ServerSideRender` in a `useBlockProps()` div:

```js
el( 'div', blockProps,
    el( ServerSideRender, {
        block: 'woocanvas/related-products',
        attributes: props.attributes,
        httpMethod: 'POST',
    } )
)
```

---

## Render (`render.php`)

### Editor preview branch

`woocommerce_output_related_products()` selects related products based on shared categories and tags with the current product. In a preview context (or a store with very few products), this returns nothing — WooCommerce finds no matches.

The editor branch works around this by injecting up to 4 most-recently-published products as the related set:

```php
$related_ids = wc_get_products([
    'limit' => 4, 'status' => 'publish', 'return' => 'ids',
    'orderby' => 'date', 'order' => 'DESC',
    'exclude' => [ $preview_ids[0] ],
]);

$inject = static function () use ( $related_ids ) { return $related_ids; };
add_filter( 'woocommerce_related_products', $inject );
woocommerce_output_related_products();
remove_filter( 'woocommerce_related_products', $inject );
```

The preview product itself is excluded from `$related_ids` so it does not appear as its own related product.

The output is wrapped in `<div class="woocanvas-related-preview">` and preceded by a `<style>` block containing all CSS needed to render the grid correctly inside the editor iframe.

**Why inline CSS?**  
The block editor canvas runs inside an `<iframe>`. Styles enqueued via `enqueue_block_editor_assets` target the outer admin document and do not reach the iframe. All CSS that the preview needs must be injected directly into the SSR response — the same pattern used by `add-to-cart` and `product-tabs`.

The inline CSS includes:
- `@font-face` for the WooCommerce star rating font (absolute URL via `WC()->plugin_url()`)
- CSS Grid layout replacing WC's float-based `.columns-*` system (`repeat(4, minmax(0, 1fr))`)
- Product card, image, title, price, rating, and button styles
- `pointer-events: none` on the wrapper so the preview is not interactive in the editor

### Frontend branch

1. `wc_get_product( get_the_ID() )` — exits silently if no product.
2. Sets `$GLOBALS['product']`.
3. Wraps `woocommerce_output_related_products()` in a `<div>` via `get_block_wrapper_attributes()`.

On the frontend, `woocommerce_output_related_products()` uses the real product's category/tag matches. If the product genuinely has no related products, the block outputs nothing (correct WooCommerce behaviour).

---

## Block supports

```json
"supports": {
  "html": false,
  "spacing": { "margin": true, "padding": true },
  "color": false,
  "border": { "radius": true, "width": true, "style": true, "color": true }
}
```

`color` support is disabled. The related products grid inherits its card/button colours from WooCommerce and the active theme; a background or text colour override on the outer wrapper would be ineffective.

---

## Known constraints

- **Empty preview on single-product stores** — If the store has only one product, `$related_ids` from `wc_get_products()` (excluding the preview product) will be empty, and the editor preview renders nothing. The placeholder message "Add a published product to see a preview" does not cover this case. For accurate previews, have at least two published products.
- **No per-block count control** — The number of related products shown is controlled globally by `woocommerce_output_related_products_args` filter (WooCommerce default: 4). A future `columns` / `limit` attribute could expose this.
- **WooCommerce CSS in the editor** — Unlike the frontend, the editor preview does not rely on `enqueue_editor_styles()`. All required CSS (grid layout, card styles, star font) is injected inline in the SSR payload so it reaches the editor canvas iframe. The `.woocommerce` wrapper is no longer used in the editor branch.
- **Single product only** — The editor preview always uses the most recently published product as the base product for the inject workaround.
