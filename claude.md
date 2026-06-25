# WooCanvas — Project Brief for AI Agent

## What is this?
A WordPress plugin that enables full Gutenberg/FSE (Full Site Editing) control over WooCommerce templates. Users can edit WooCommerce templates as native block templates in the WordPress Site Editor.

## Vision
WooCommerce templates (starting with single product) should be editable with complete Gutenberg freedom — blank canvas, no opinionated layout imposed by the theme or WooCommerce itself.

---

## Scope — v1.0

**In scope:**
- Single product page template only
- Block theme detection + error notice with list of popular block themes if none active
- 2–3 starter templates (e.g. minimal, classic, full-width)
- Wraps existing WooCommerce blocks — no custom blocks unless WooCommerce falls short
- Templates editable natively in WordPress Site Editor (no custom admin UI)

**Out of scope for v1.0:**
- Shop archive, cart, checkout, account pages (future versions)
- Custom block development beyond essential gaps
- Premium/paid features — this is 100% free and open source

---

## Technical Requirements

- **Requires an active block theme** — plugin throws an admin error and lists popular block themes if none detected. No silent fallback, no bundled micro-theme.
- Minimum WooCommerce version: TBD (pin to version that supports block template API)
- Target: WordPress.org distribution

---

## Architecture Decisions

- Register block templates via WooCommerce's block template system (mirror `BlockTemplatesController` pattern)
- Templates stored as block HTML in `/templates/` folder inside the plugin
- No companion theme shipped — block theme is a hard user prerequisite
- Native Site Editor experience — plugin should be invisible once set up

---

## Key Constraints

- "Woo" trademark: do NOT use "Woo" in plugin name or branding
- WooCommerce block API is a moving target — accept maintenance burden, pin minimum WC version
- WordPress.org guidelines must be followed for distribution

---

## What to build first
1. Plugin scaffold (headers, activation hook)
2. Block theme detection + admin notice with popular theme list
3. Single product block template registration
4. 2–3 starter templates in block HTML format
5. README and WordPress.org assets

---

## Local Development

- URL: http://blocktheme.local/
- Username: admin
- Password: admin

### Browsing the site with Playwright

Playwright (v1.61.1) is available via npx and can be used to browse the site headlessly. Use the module cached at `/Users/sebastiaan/.npm/_npx/e41f203b7505f1fb/node_modules/playwright`.

To log in to wp-admin:

```js
const { chromium } = require('/Users/sebastiaan/.npm/_npx/e41f203b7505f1fb/node_modules/playwright');
(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.goto('http://blocktheme.local/wp-login.php');
  await page.fill('#user_login', 'admin');
  await page.fill('#user_pass', 'admin');
  await page.click('#wp-submit');
  await page.waitForLoadState('networkidle');
  // page is now at http://blocktheme.local/wp-admin/
  await browser.close();
})();
```

Run inline with: `node -e "<script>"` or save to a `.mjs` file and run with `node`.

---

## Notes
- Plugin name is TBD — "WooCanvas" is a working title, final name TBD
- Keep code clean for open source — document hooks and filters for extensibility