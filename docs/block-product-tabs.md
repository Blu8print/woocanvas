# Block: `woocanvas/product-tabs`

Renders the WooCommerce product data tabs: Description, Additional Information, and Reviews.

---

## Files

```
blocks/product-tabs/
  block.json   — Block metadata, attributes, supports
  index.js     — Editor registration + InspectorControls
  render.php   — Server-side render (editor preview + frontend)
```

---

## Attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `tabStyle` | `string` | `"default"` | Visual style of the tab bar. See presets below. |
| `activeColor` | `string` | `""` | Accent color for the active tab. Empty = WC default. |
| `tabAlignment` | `string` | `"left"` | Horizontal alignment of the tab bar. |
| `showReviews` | `boolean` | `true` | Whether to include the Reviews tab. |

### Tab style presets

| Value | Appearance |
|---|---|
| `default` | WooCommerce's bordered tabs with rounded-top corners and grey inactive background |
| `underline` | Flat tab labels; active tab has a 2 px bottom border in `activeColor` |

### Active color behaviour

| Style | What the color changes |
|---|---|
| `default` | Active tab top-border accent strip + active link text color |
| `underline` | Active tab bottom-border + active link text color |

When `activeColor` is empty the WC/theme defaults apply (no override emitted).

### Alignment values

| Value | CSS effect |
|---|---|
| `left` | `ul.tabs` retains WC's default `padding-left: 1em` indent |
| `center` | `display: flex; justify-content: center; padding-left: 0` |
| `right` | `display: flex; justify-content: flex-end; padding-left: 0` |

---

## Editor (`index.js`)

Plain IIFE — no JSX, no build step. Registered as `woocanvas-product-tabs-editor` in `class-block-registrar.php` with `wp-server-side-render` as an explicit dependency.

The edit function renders:

1. `InspectorControls` → `PanelBody` ("Tab Style") containing:
   - `SelectControl` — Style (Default / Underline)
   - `SelectControl` — Alignment (Left / Center / Right)
   - `BaseControl` + `ColorPalette` — Active Tab Color (uses theme palette + custom picker)
   - `ToggleControl` — Show Reviews tab (on/off)
2. `ServerSideRender` — re-renders on every attribute change via POST to the block-renderer REST endpoint.

---

## Render (`render.php`)

The file has two branches detected via `defined('REST_REQUEST') && REST_REQUEST`, plus two shared CSS builder closures defined at the top.

### CSS builders

**`$build_editor_css()`**  
Returns the complete CSS for the editor preview. Because `enqueue_block_editor_assets` targets the outer admin frame (not the canvas iframe), WC CSS never reaches the SSR preview. This function emits all base tab styles from scratch, scoped to `.woocanvas-tabs-preview`, then switches the `li` / `li.active` rules based on `$tab_style`.

**`$build_frontend_css( $id )`**  
Returns override-only CSS scoped to `#woocanvas-tabs-{id}`. WooCommerce's `woocommerce.css` provides the default bordered tab appearance, so this function only emits rules when attributes deviate from WC defaults:
- Nothing emitted when `tabStyle === 'default'`, `activeColor === ''`, `tabAlignment === 'left'`.
- `underline` style — strips WC borders/backgrounds, adds 2 px bottom border on `li.active`.
- `activeColor` on `default` style — top-border accent + link color on `li.active`.
- `center` / `right` alignment — adds `display: flex` + `justify-content` to `ul.tabs`.

An ID selector (`#woocanvas-tabs-n`) has higher specificity than WC's class selectors, so no `!important` is needed.

### Reviews tab filtering

When `showReviews` is `false`, a `woocommerce_product_tabs` filter (priority 99) is added before calling `woocommerce_output_product_data_tabs()` to `unset( $tabs['reviews'] )`. The filter is removed after rendering to avoid affecting any subsequent block instances on the same page.

### Editor preview branch

1. Fetch the most recently published product via `wc_get_products()`.
2. Set `global $post` + `setup_postdata()` + `$GLOBALS['product']` for WC context.
3. Output `<style>` from `$build_editor_css()` directly in the SSR payload.
4. Wrap `woocommerce_output_product_data_tabs()` in `<div class="woocanvas-tabs-preview">`.

### Frontend branch

1. `wc_get_product( get_the_ID() )` — exits silently if no product.
2. Generates a unique `$block_id` via `wp_unique_id('woocanvas-tabs-')`.
3. Emits `<style>$build_frontend_css($block_id)</style>` only when the string is non-empty.
4. Wraps `woocommerce_output_product_data_tabs()` in a `<div id="{block_id}">` via `get_block_wrapper_attributes()`.

---

## CSS architecture

### Editor — always scoped to `.woocanvas-tabs-preview`

| Rule | Purpose |
|---|---|
| `pointer-events: none` | Prevents tab clicks in the editor canvas |
| `ul.tabs` base layout | Replicates WC's `overflow: hidden; position: relative` container |
| `ul.tabs::before` bottom line | The horizontal rule under all tabs |
| `li` + `li.active` — style-specific | Default: WC bordered look. Underline: flat + bottom border. |
| `.wc-tab { display: none }` / `.wc-tab:first-of-type { display: block }` | Show only the first (Description) panel — mirrors jQuery UI tabs default |

### Frontend — scoped to `#woocanvas-tabs-{n}` (only when needed)

No output if all attributes are at their defaults. Otherwise emits the minimal set of overrides required.

---

## Block supports

```json
"supports": {
  "html": false,
  "spacing": { "margin": true, "padding": true },
  "border": { "radius": true, "width": true, "style": true, "color": true }
}
```

The `color` support key is omitted entirely — the `activeColor` attribute provides targeted color control and a free text/background color picker would conflict with WC's tab styling.

---

## Known constraints

- **jQuery UI tabs** — Tab switching is JS-driven on the frontend; the editor shows only the first panel (Description) as a static preview.
- **WooCommerce CSS in the editor** — WC CSS is in the outer admin frame, not the canvas iframe. The editor branch emits all needed CSS inline via the SSR payload.
- **Third-party tabs** — Any plugin registering tabs via `woocommerce_product_tabs` filter will appear automatically on both frontend and in the editor preview.
- **Single product only** — The editor preview always uses the most recently published product.
