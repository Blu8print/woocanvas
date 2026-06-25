# Block: `woocanvas/add-to-cart`

Renders the WooCommerce add to cart form — quantity field with +/− stepper, variation selectors (for variable products), and the submit button.

---

## Files

```
blocks/add-to-cart/
  block.json   — Block metadata, attributes, supports
  index.js     — Editor registration
  render.php   — Server-side render (editor preview + frontend)
```

---

## Editor (`index.js`)

Plain IIFE — no JSX, no build step. Registered as `woocanvas-add-to-cart-editor` in `class-block-registrar.php`.

The edit function passes `className: 'woocommerce'` into `useBlockProps()`:

```js
var blockProps = useBlockProps( { className: 'woocommerce' } );
```

This ensures the `woocommerce` CSS scope class is present on the outer block wrapper element in the editor canvas, where it is available as an ancestor for any CSS that manages to load into the iframe (e.g. WC's global stylesheet if it ever loads in canvas context). The primary styling, however, is handled by the inline `<style>` block in the SSR payload (see below).

---

## Render (`render.php`)

### Editor preview branch

Triggered on every `ServerSideRender` REST request (`defined('REST_REQUEST') && REST_REQUEST`).

**Steps:**

1. Fetch the most recently published product via `wc_get_products()`.
2. Set `global $post` + `setup_postdata()` + `$GLOBALS['product']` so `woocommerce_template_single_add_to_cart()` has product context.
3. Capture the form HTML via `ob_start()` / `ob_get_clean()`.
4. Inject quantity stepper buttons into the captured HTML (see [Stepper injection](#stepper-injection)).
5. Output a scoped `<style>` block into the SSR payload (see [Editor CSS strategy](#editor-css-strategy)).
6. Wrap the modified form HTML in `<div class="woocanvas-atc-preview">`.

### Frontend branch

1. `wc_get_product( get_the_ID() )` — exits silently if no product.
2. Sets `$GLOBALS['product']`.
3. Outputs via `get_block_wrapper_attributes( [ 'class' => 'woocommerce' ] )` — merges `woocommerce` into the block's default class list (alongside spacing, border, colour support classes injected by WordPress).
4. Calls `woocommerce_template_single_add_to_cart()` inside the wrapper.

---

## CSS architecture

### Why `.woocommerce` on the wrapper

WooCommerce scopes all of its form styles under the `.woocommerce` ancestor class — selectors like `.woocommerce form .quantity` and `.woocommerce .single_add_to_cart_button` only apply when `.woocommerce` is above the form in the DOM.

`woocommerce_template_single_add_to_cart()` does **not** output a `.woocommerce` wrapper itself; it only outputs `<form class="cart">` and its contents. The wrapper must come from the block.

Adding `woocommerce` to `get_block_wrapper_attributes()` on the frontend and to `useBlockProps()` in the editor places it on the outermost block element in both contexts.

### Editor CSS strategy

WooCommerce's form layout depends on rules scoped to `.woocommerce div.product form.cart` (for floats / flex). The `div.product` wrapper exists on the frontend because WooCommerce's single-product page template wraps the entire product in a `<div class="product">`. Inside the block editor canvas there is no such wrapper, so those WC rules do not apply.

CSS enqueued via `enqueue_block_editor_assets` targets the **outer admin frame**, not the editor canvas iframe. Styles from that hook are not guaranteed to reach the block preview.

The solution is to output a `<style>` tag directly inside the SSR payload. SSR output is injected into the canvas iframe by `ServerSideRender`, so a `<style>` block within it applies reliably. All layout and appearance CSS for the editor preview is delivered this way, scoped to `.woocanvas-atc-preview`.

### Stepper injection

WooCommerce's quantity input template (`global/quantity-input.php`) outputs a plain `<input type="number">`. The +/− stepper shown on the frontend is added by GeneratePress Premium's WooCommerce module via jQuery:

```js
// GP Premium woocommerce.js (simplified)
box.parent().addClass('buttons-added').prepend('<a class="minus">-</a>');
box.after('<a class="plus">+</a>');
```

This JS does not run in the editor iframe. The editor preview branch replicates the same DOM mutation in PHP using string replacement:

```php
// Add buttons-added class + prepend minus (first child of .quantity)
$form_html = str_replace(
    '<div class="quantity">',
    '<div class="quantity buttons-added"><a href="javascript:void(0)" class="minus">-</a>',
    $form_html
);
// Append plus after the input's self-closing tag
$form_html = preg_replace(
    '/<input\b.*?\/>/s',
    '$0<a href="javascript:void(0)" class="plus">+</a>',
    $form_html,
    1
);
```

The resulting DOM structure in the editor preview:

```html
<div class="quantity buttons-added">
  <a class="minus">-</a>
  <label class="screen-reader-text">…</label>   <!-- visually hidden by preview CSS -->
  <input class="input-text qty text" …>
  <a class="plus">+</a>
</div>
```

### Preview CSS rules

All rules in the `<style>` block are scoped to `.woocanvas-atc-preview`. They are safe as globals within the canvas because this class only appears in editor SSR output.

| Rule target | Property | Effect |
|---|---|---|
| `form.cart` | `display: flex; align-items: center; gap: 4px` | Quantity + button side by side |
| `.quantity.buttons-added` | `display: flex; align-items: center` | Stepper box layout |
| `.minus`, `.qty`, `.plus` | `width/height: 50px; border: 1px solid rgba(0,0,0,.1)` | Uniform box size with shared border |
| `.minus` | `border-right-width: 0` | Shared border between minus and qty |
| `.plus` | `border-left-width: 0` | Shared border between qty and plus |
| `.qty` | `-webkit-appearance: none; -moz-appearance: textfield` | Remove browser spin buttons |
| `.screen-reader-text` | `position: absolute; clip: rect(0,0,0,0)` | Hide the quantity label visually |
| `.single_add_to_cart_button` | `background-color: var(--wc-primary, #2271b1)` | Blue button matching WC colour variable |

---

## Block supports

```json
"supports": {
  "html": false,
  "spacing": { "margin": true, "padding": true },
  "color": { "background": true, "text": false },
  "border": { "radius": true, "width": true, "style": true, "color": true }
}
```

WordPress merges the resulting classes and inline styles into the wrapper via `get_block_wrapper_attributes()` on the frontend.

---

## Known constraints

- **Non-interactive preview** — `.woocanvas-atc-preview` has `pointer-events: none`, so the button, quantity input, and stepper anchors are all unclickable. This prevents the form from submitting (and adding a product to the cart) when clicked in the editor. The outer block wrapper remains interactive for block selection.
- **Variable product JS** — For variable products, WooCommerce's variation JS shows/hides attribute dropdowns, updates the price, and enables the button when a valid combination is selected. This JS does not run in the editor. Variation selectors render as static dropdowns in the preview.
- **Theme accent colour** — The button colour in the editor uses `var(--wc-primary)` from WooCommerce's base CSS (defaults to `#720eec`). If the active theme overrides this variable via customizer-generated CSS, the colour in the editor may differ slightly from the frontend. This is a cosmetic limitation of CSS not loading into the editor canvas.
- **Single product only** — The editor preview always uses the most recently published product. There is no per-block product picker.
