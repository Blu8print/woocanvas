=== WooCanvas ===
Contributors: (your WordPress.org username)
Tags: woocommerce, block, fse, full-site-editing, single-product
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
WC requires at least: 9.4
WC tested up to: 10.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Full Gutenberg/FSE control over WooCommerce templates. Edit your single product page as a native block template in the WordPress Site Editor.

== Description ==

WooCanvas gives you a blank canvas for your WooCommerce single product page. Instead of fighting opinionated theme and WooCommerce layouts, you get a clean starting point built entirely from native WooCommerce blocks — fully editable in the WordPress Site Editor (Appearance → Editor → Templates).

**What it does**

* Replaces the default WooCommerce single product template with a clean, layout-agnostic block template.
* Three starter layouts to choose from: **Minimal** (two-column, image + details), **Classic** (stacked, full-width), and **Full-Width** (hero image + content below).
* Works natively in the Site Editor — no custom admin UI, no page builders.
* Your edits are saved by WordPress and are never overwritten by the plugin.

**Requirements**

* An active **block (FSE) theme** — the plugin shows a clear error notice with popular theme recommendations if none is detected.
* WooCommerce 9.4 or higher.
* WordPress 6.7 or higher.

**Choosing a starter layout**

The default starter is "minimal". Developers can switch starters via a filter:

`add_filter( 'woocanvas_starter_template', fn() => 'classic' );`

Available values: `minimal`, `classic`, `full-width`.

**Once you've customised the template in the Site Editor, this filter has no effect** — your saved version takes full precedence.

== Installation ==

1. Upload the `woocanvas` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Make sure a block theme is active (e.g. Twenty Twenty-Five).
4. Go to **Appearance → Editor → Templates → Single Product** to start editing.

== Frequently Asked Questions ==

= Does this work with classic (non-FSE) themes? =

No. A block theme is required. The plugin shows an admin notice listing popular block themes if your current theme is not compatible.

= Will my customisations be lost when the plugin updates? =

No. Once you save a customisation in the Site Editor, WordPress stores it in the database. The plugin's starter content is only used as the initial fallback — it is never applied on top of your saved edits.

= How do I reset to a starter template? =

In the Site Editor, open **Templates → Single Product**, click the three-dot menu, and choose **Clear customisations**. The plugin's starter will then be used again.

= Can I use multiple starter templates for different products? =

Not in v1.0. The `woocanvas_starter_template` filter lets you pick one global starter. Per-product template overrides are planned for a future release.

== Changelog ==

= 0.1.0 =
* Initial release.
* Single product blank-canvas template with three starter layouts: minimal, classic, full-width.
* Block theme detection with admin notice.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
