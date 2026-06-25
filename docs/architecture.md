# WooCanvas — Architecture & Development Log

**Plugin slug:** `working-title` (final name TBD — "WooCanvas" is the working title)
**Version:** 0.1.0
**Entry point:** `woocanvas.php`
**Min WP:** 6.7 | **Min WC:** 9.4 | **Min PHP:** 8.1

---

## What this plugin does

WooCanvas lets users design WooCommerce single-product page layouts using the WordPress block editor. Instead of WooCommerce's rigid default output, the product page renders a user-built block template.

The editing happens inside a private custom post type (`woocanvas_template`) accessible via **WooCommerce → Product Template** in the admin menu. The user builds a layout there using WooCanvas blocks (and any other blocks), then WooCanvas injects that layout onto every single product page at runtime.

---

## File map

```
woocanvas.php                        — Plugin bootstrap, constants, activation hook
includes/
  class-cpt-manager.php             — Registers the woocanvas_template CPT + admin submenu
  class-product-renderer.php        — Injects the block template onto product pages
  class-wc-dynamic-tags.php         — GenerateBlocks dynamic tags for WC product fields
  class-block-registrar.php         — Registers blocks, editor scripts, and editor styles
blocks/
  product-gallery/
    block.json                      — Block metadata, supports, script handle
    index.js                        — Block edit function (ServerSideRender)
    render.php                      — Server-side render (frontend + editor preview)
  add-to-cart/
    block.json / index.js / render.php
  product-tabs/
    block.json / index.js / render.php
  related-products/
    block.json / index.js / render.php
docs/
  architecture.md                   — This file
  block-product-gallery.md          — Gallery block detail
  block-add-to-cart.md              — Add to Cart block detail
  block-product-tabs.md             — Product Tabs block detail
  block-related-products.md         — Related Products block detail
```

---

## Class responsibilities

### `WooCanvas_CPT_Manager` (`class-cpt-manager.php`)

Owns the single template post.

- Registers the `woocanvas_template` CPT (private, block-editor enabled, `show_in_rest: true`).
- Adds a **WooCommerce → Product Template** submenu that redirects straight to `post.php?action=edit` for the template post.
- `get_or_create_template_post()` — idempotent; creates the post once and stores its ID in `woocanvas_template_id` option. Called on plugin activation and on each submenu redirect.

### `WooCanvas_Product_Renderer` (`class-product-renderer.php`)

Hooks into `template_redirect` on every single product page.

- Reads the template post's `post_content`.
- If it has content: strips WooCommerce's default summary and gallery hooks, then outputs `do_blocks($content)` via `woocommerce_before_single_product_summary` at priority 5.
- If it's empty: falls back silently to WooCommerce's default rendering.

### `WooCanvas_WC_Dynamic_Tags` (`class-wc-dynamic-tags.php`)

Bridges WooCommerce product data into GenerateBlocks' dynamic tag system (`{{wc_*}}` syntax).

Available tags:

| Tag | Returns |
|-----|---------|
| `wc_product_title` | Product name |
| `wc_product_price` | Formatted price HTML (with sale/regular) |
| `wc_product_regular_price` | Regular price formatted |
| `wc_product_sale_price` | Sale price formatted |
| `wc_product_short_description` | Short description (auto-p'd) |
| `wc_product_description` | Full description (auto-p'd) |
| `wc_product_sku` | SKU |
| `wc_product_stock_status` | "In stock" / "Out of stock" / "On backorder" |
| `wc_product_stock_quantity` | Raw number |
| `wc_product_categories` | Linked category list |
| `wc_product_tags` | Linked tag list |
| `wc_product_image_url` | Full-size featured image URL |
| `wc_product_rating` | Average rating (string) |
| `wc_product_review_count` | Number of reviews |
| `wc_product_permalink` | Product URL |
| `wc_product_add_to_cart_url` | Add-to-cart URL |
| `wc_product_weight` | Formatted weight with unit |
| `wc_product_dimensions` | Formatted L×W×H |

Only registers when `GenerateBlocks_Register_Dynamic_Tag` class exists. Safe to have active without GenerateBlocks installed.

### `WooCanvas_Block_Registrar` (`class-block-registrar.php`)

Wires up the four custom blocks.

- **`register_category`** — Adds a "WooCanvas" category to the block inserter, positioned after GenerateBlocks (priority 11 vs GenerateBlocks' 10).
- **`register_editor_scripts`** — Registers each block's `index.js` via PHP (not `block.json`) so we can declare `wp-server-side-render` as an explicit dependency. This is the critical path for the editor preview.
- **`register_blocks`** — Calls `register_block_type()` for all four blocks from their `block.json`.
- **`enqueue_editor_styles`** — Enqueues WooCommerce's frontend CSS inside the block editor (see below for why this is needed).

---

## The four blocks

All four blocks share the same structural pattern:

- **`block.json`** — Block metadata with `editorScript` pointing to a PHP-registered handle (not `file:./index.js`). No `viewScript` — all interactivity comes from WooCommerce's own frontend JS.
- **`index.js`** — Edit function wraps `ServerSideRender` with `useBlockProps`. No React JSX — plain IIFE using `window.wp.*` globals. Three blocks add `InspectorControls` for custom attributes; `related-products` has none.
- **`render.php`** — Two branches: `REST_REQUEST` (editor preview) and the frontend path.

| Block | WC function called | Custom attributes | InspectorControls |
|-------|-------------------|--------------------|-------------------|
| `woocanvas/product-gallery` | `woocommerce_show_product_images()` | `maxWidth`, `aspectRatio`, `thumbnailPosition` | Yes |
| `woocanvas/add-to-cart` | `woocommerce_template_single_add_to_cart()` | `layout`, `buttonColor`, `buttonTextColor` | Yes |
| `woocanvas/product-tabs` | `woocommerce_output_product_data_tabs()` | `tabStyle`, `activeColor`, `tabAlignment`, `showReviews` | Yes |
| `woocanvas/related-products` | `woocommerce_output_related_products()` | — | No |

### Block supports

Each block's `block.json` declares its own `supports`. `spacing` and `border` are enabled across all four; `color` varies:

| Block | `color` support |
|-------|----------------|
| `product-gallery` | `{ "background": true, "text": false }` |
| `add-to-cart` | `false` (uses `buttonColor`/`buttonTextColor` attributes instead) |
| `product-tabs` | omitted entirely (uses `activeColor` attribute instead) |
| `related-products` | `false` |

All four have `"html": false` and `"spacing": { "margin": true, "padding": true }` and `"border": { "radius": true, "width": true, "style": true, "color": true }`.

The frontend `render.php` path always uses `get_block_wrapper_attributes()` to apply these to the wrapper `<div>`, which is how WordPress injects the generated inline styles and class names.

---

## Editor preview system

### How it works

The block editor uses WordPress's `ServerSideRender` component, which POSTs to `/wp-json/wp/v2/block-renderer/{namespace}/{block-name}`. WordPress calls the block's `render.php` inside that REST request.

Each `render.php` detects the editor context with:

```php
if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
    // editor preview branch
}
```

In the preview branch, a real published product is fetched via `wc_get_products()`, set as the global `$post` and `$GLOBALS['product']`, and then the WooCommerce template function is called. This renders authentic WooCommerce HTML (real product images, real prices, real form fields).

### Why real products, not fake data

WooCommerce's template functions (`woocommerce_show_product_images()` etc.) rely heavily on `get_the_ID()`, `$GLOBALS['product']`, and internal WC state. Mocking this with a fake `WC_Product_Simple` object is fragile — functions like `has_post_thumbnail()` call into WordPress post meta, which only works with a real post ID and real post data. The real-product approach is more reliable and shows accurate output.

### WooCommerce CSS in the editor

WooCommerce only registers its CSS handles (`woocommerce-general` etc.) on the frontend via `wp_enqueue_scripts`. In the admin/editor context, those handles don't exist. `wp_enqueue_style('woocommerce-general')` silently does nothing.

Fix in `enqueue_editor_styles()`: enqueue WC CSS directly by URL using `WC()->plugin_url()`:

```php
$wc_url = WC()->plugin_url() . '/assets/css/';
wp_enqueue_style( 'woocanvas-wc-general', $wc_url . 'woocommerce.css', [], $wc_ver );
wp_enqueue_style( 'woocanvas-wc-layout', $wc_url . 'woocommerce-layout.css', [], $wc_ver );
wp_enqueue_style( 'woocanvas-wc-smallscreen', $wc_url . 'woocommerce-smallscreen.css', ... );
```

### Gallery opacity fix

WooCommerce renders the product gallery with `style="opacity: 0; transition: opacity .25s ease-in-out;"` and relies on its own JS (`wc-single-product.js`) to fade it in after the gallery initialises. That JS does not run in the block editor context, so the gallery stays invisible.

**The fix is applied server-side** in `blocks/product-gallery/render.php` for the preview branch:

```php
ob_start();
woocommerce_show_product_images();
echo str_replace( 'style="opacity: 0; transition: opacity .25s ease-in-out;"', '', ob_get_clean() );
```

A CSS override (`opacity: 1 !important`) is also enqueued as a belt-and-suspenders measure, but the string replacement is the primary fix because the block editor canvas runs inside an `<iframe>` and outer-document stylesheets do not reach inside it.

### `wp-server-side-render` dependency

`ServerSideRender` is not in WordPress core's default block editor bundle — it must be declared as a dependency. WordPress does not allow declaring dependencies in `block.json` for scripts registered with `file:./` relative paths in the way needed here.

The solution: register scripts via PHP in `register_editor_scripts()` with an explicit dependency array, then reference the handle name (not a file path) in `block.json`:

```json
"editorScript": "woocanvas-product-gallery-editor"
```

```php
wp_register_script(
    'woocanvas-product-gallery-editor',
    WOOCANVAS_PLUGIN_URL . 'blocks/product-gallery/index.js',
    [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-server-side-render' ],
    WOOCANVAS_VERSION,
    true
);
```

### Related products preview

`woocommerce_output_related_products()` picks related products based on category/tag matching for the current product. In a preview context where only one product exists, it returns nothing. A `woocommerce_related_products` filter is temporarily added to inject the most-recent 4 products (excluding the preview product itself):

```php
$related_ids = wc_get_products([ 'limit' => 4, 'exclude' => [ $preview_ids[0] ] ]);
$inject = static function () use ( $related_ids ) { return $related_ids; };
add_filter( 'woocommerce_related_products', $inject );
woocommerce_output_related_products();
remove_filter( 'woocommerce_related_products', $inject );
```

---

## Bootstrap sequence

```
plugins_loaded
  ├─ WC present + version OK?
  │     ├─ WooCanvas_CPT_Manager::init()
  │     │     └─ init → register_post_type()
  │     │     └─ admin_menu → add_submenu()
  │     ├─ WooCanvas_Product_Renderer::init()
  │     │     └─ template_redirect → maybe_override()
  │     ├─ WooCanvas_WC_Dynamic_Tags::init()
  │     │     └─ init (priority 20) → register()
  │     └─ WooCanvas_Block_Registrar::init()
  │           └─ block_categories_all (priority 11) → register_category()
  │           └─ init → register_editor_scripts()
  │           └─ init → register_blocks()
  │           └─ enqueue_block_editor_assets → enqueue_editor_styles()
  └─ WC missing/outdated → admin_notices error
```

---

## Known limitations & next steps

### Current gaps

- **Single product only** — Shop archive, cart, checkout, My Account pages are out of scope for v1.0.
- **One global template** — All products share the same template. Per-product or per-category templates are future scope.
- **No starter templates** — The template post starts empty. Users build from scratch. Pre-built starter layouts (minimal, classic, full-width) are planned for v1.0 but not yet implemented.
- **No block theme detection** — Plugin does not yet check for an active block theme and does not show an admin notice if a classic theme is active.
- **Related Products empty on single-product stores** — When a store has only one product, the related products preview injects nothing (no other products to show). The editor displays a blank block rather than a helpful placeholder.

### Planned for v1.0

1. Block theme detection + admin notice with popular theme list
2. 2–3 starter templates (block HTML saved as default post content or selectable on first use)
3. WordPress.org assets (banner, icon, screenshots)
4. README.txt polishing

### Extension points (filters/actions)

The codebase is designed to be hook-friendly. Current filter opportunities:

- `woocommerce_related_products` — already used internally for preview injection; third parties can hook it too.
- `block_categories_all` — WooCanvas registers at priority 11; third parties at higher priorities can reorder further.
- `woocanvas_template_content` — not yet implemented, but a natural place to filter the block content before `do_blocks()` in `WooCanvas_Product_Renderer`.

---

## Local development

- **URL:** http://blocktheme.local/
- **Credentials:** admin / admin
- **Playwright:** available at `/Users/sebastiaan/.npm/_npx/e41f203b7505f1fb/node_modules/playwright` (v1.61.1) — used during development to verify editor block rendering and REST endpoint responses.

### Testing the editor preview

1. Go to **WooCommerce → Product Template** in wp-admin.
2. Insert any of the four WooCanvas blocks from the block inserter (WooCanvas category).
3. Each block should render a live preview using the most-recently-published product from the store.
4. If no published products exist, a placeholder message is shown.

### Testing the frontend

1. Ensure the template post has content (at least one block).
2. Visit any single product page.
3. WooCanvas output replaces WooCommerce's default summary/gallery; WooCommerce's outer product container (`<div class="product">`) remains.
