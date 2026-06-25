# Block: `woocanvas/product-gallery`

Renders the WooCommerce product image gallery with configurable size and thumbnail strip controls.

---

## Files

```
blocks/product-gallery/
  block.json   — Block metadata, attributes, supports
  index.js     — Editor registration and InspectorControls
  render.php   — Server-side render (editor preview + frontend)
```

---

## Attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `maxWidth` | `string` | `""` | CSS value applied as `max-width` to the gallery wrapper (e.g. `500px`, `60%`). Empty = no constraint. |
| `aspectRatio` | `string` | `"natural"` | Preset key controlling the main image crop. See table below. |
| `thumbnailPosition` | `string` | `"below"` | Where the thumbnail strip appears. See table below. |

### Aspect ratio presets

| Value | CSS `aspect-ratio` |
|---|---|
| `natural` | none applied |
| `1-1` | `1 / 1` |
| `4-3` | `4 / 3` |
| `3-4` | `3 / 4` |
| `16-9` | `16 / 9` |

### Thumbnail position values

| Value | Behaviour |
|---|---|
| `below` | Strip below the main image (default) |
| `top` | Strip above the main image |
| `left` | Strip in a vertical column to the left |
| `right` | Strip in a vertical column to the right |
| `hidden` | No strip shown |

---

## Editor (`index.js`)

Plain IIFE — no JSX, no build step. Registered as `woocanvas-product-gallery-editor` in `class-block-registrar.php` with explicit dependencies so `wp-server-side-render` is available.

The edit function renders:

1. `InspectorControls` → `PanelBody` ("Gallery Style") containing:
   - `TextControl` — Max Width
   - `SelectControl` — Aspect Ratio (5 presets)
   - `SelectControl` — Thumbnails (5 positions)
2. `ServerSideRender` — calls `/wp-json/wp/v2/block-renderer/woocanvas/product-gallery` via POST on every attribute change, displaying the PHP-rendered preview inside the editor canvas.

---

## Render (`render.php`)

The file has two distinct branches detected via `defined('REST_REQUEST') && REST_REQUEST`.

### Editor preview branch

Triggered on every `ServerSideRender` POST request.

**Steps:**
1. Fetch the most recently published product via `wc_get_products()`.
2. Set `global $post` + `setup_postdata()` + `$GLOBALS['product']` so WooCommerce template functions have context.
3. Render the main image with `wp_get_attachment_image( $id, 'woocommerce_single' )`.
4. Apply `aspect-ratio` and `object-fit` as **inline styles directly on the `<img>` tag**. This is intentional — `wp_get_attachment_image` always outputs `width="X" height="Y"` HTML attributes which would override a `<style>` block rule; inline styles win unconditionally.
5. Build the custom thumbnail strip via `$build_thumb_strip()`.
6. Wrap main image + strip in a flex container whose direction depends on `thumbnailPosition`.

**Why a custom thumbnail strip?**  
`woocommerce_show_product_images()` outputs WooCommerce's Flexslider HTML. Flexslider initialises the gallery (creating `.flex-viewport`, converting images to slides, building `.flex-control-thumbs`) via JavaScript. That JS does not run inside the block editor iframe, so calling `woocommerce_show_product_images()` in the editor would render all gallery images at full size in a stack rather than as a slider. The custom strip gives an accurate visual preview without depending on Flexslider.

**Thumbnail strip rules:**
- Collects featured image ID + gallery image IDs.
- `array_filter()` removes falsy IDs (product with no featured image).
- Pads to a minimum of 3 items with placeholder divs (grey box, dashed border, picture SVG icon) so the strip is never empty.
- Items are 68 × 68 px, `object-fit: cover`.
- Left/right positions render the strip as a vertical column (`flex-direction: column; width: 72px`).

**DOM order determines visual position:**
- `top` / `left` → strip rendered first in HTML, image second.
- `below` / `right` / `hidden` → image rendered first, strip second.
- No `order` CSS tricks are used.

### Frontend branch

1. `wc_get_product( get_the_ID() )` — exits silently if no product.
2. Generates a unique block ID via `wp_unique_id('woocanvas-gallery-')`.
3. Outputs a scoped `<style>` block from `$build_css()` then a wrapper `<div>` via `get_block_wrapper_attributes()` (carries block support styles — spacing, border, background colour).
4. Calls `woocommerce_show_product_images()` inside the wrapper. Flexslider runs normally on the frontend, producing its standard slider HTML.

---

## CSS architecture

### Frontend scoped styles (`$build_css`)

All rules are prefixed with `#woocanvas-gallery-{n}` so multiple gallery blocks on the same page don't interfere.

| Setting | Target selector | Property |
|---|---|---|
| `maxWidth` | `#id` | `max-width` |
| `aspectRatio` (non-natural) | `#id .woocommerce-product-gallery__image img` | `aspect-ratio`, `object-fit: cover`, `width/height: 100%` |
| `thumbnailPosition: hidden` | `#id .flex-control-thumbs` | `display: none` |
| `thumbnailPosition: top` | `#id .woocommerce-product-gallery` | `flex-direction: column-reverse` |
| `thumbnailPosition: left` | `#id .woocommerce-product-gallery` | `flex-direction: row-reverse` + column thumb sizing |
| `thumbnailPosition: right` | `#id .woocommerce-product-gallery` | `flex-direction: row` + column thumb sizing |

**`top` uses `column-reverse`** because Flexslider puts `.flex-viewport` before `.flex-control-thumbs` in the DOM. Reversing the column order moves the thumbs visually above the main image without touching the HTML.

**`left` uses `row-reverse`** for the same reason — thumbs come after the viewport in the DOM, so reversing the row puts them on the left.

### Editor-only styles (`$thumb_strip_item_css`)

Unscoped global CSS output inside the editor `<style>` tag:

```css
.woocanvas-thumb-item          { width: 68px; height: 68px; overflow: hidden; border: 1px solid #ddd; border-radius: 4px; flex-shrink: 0; }
.woocanvas-thumb-item img      { width: 100%; height: 100%; object-fit: cover; display: block; }
.woocanvas-thumb-placeholder   { background: #f5f5f5; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; }
```

These classes only appear in editor-generated HTML so global scope is safe.

---

## Block supports

Declared in `block.json`. WordPress injects the resulting classes and inline styles via `get_block_wrapper_attributes()` on the frontend.

```json
"supports": {
  "html": false,
  "spacing": { "margin": true, "padding": true },
  "color": { "background": true, "text": false },
  "border": { "radius": true, "width": true, "style": true, "color": true }
}
```

---

## Known constraints

- **Flexslider JS** — WooCommerce's gallery slider does not initialise inside the block editor iframe. The editor preview intentionally does not attempt to replicate the slider; it shows the main image and a static thumbnail strip instead.
- **WooCommerce CSS in the editor** — WC only registers its CSS handles on the frontend. `class-block-registrar.php` enqueues WC CSS directly by URL in `enqueue_editor_styles()` so the gallery renders with correct styles in the editor canvas.
- **Gallery opacity** — `woocommerce_show_product_images()` outputs `style="opacity:0"` and relies on WC JS to fade the gallery in. On the frontend this works normally. This attribute is irrelevant in the editor branch since we do not call that function there.
- **Single product only** — The editor preview always uses the most recently published product. There is no per-block product picker.
