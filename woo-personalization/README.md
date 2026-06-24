# Woo Personalization

WooCommerce plugin for **t-shirt (and apparel) personalization**: customers upload a design, preview it on a mockup, and files are attached to the order for production.

[![PHP Lint](https://github.com/oxcmd/woo-personalization/actions/workflows/php-lint.yml/badge.svg)](https://github.com/oxcmd/woo-personalization/actions/workflows/php-lint.yml)

## Features

- **Mockup template library** — CPT with drag-and-drop print area editor
- **Per-product settings** — enable personalization, pick template, override print area
- **Live product preview** — upload image, see composite mockup before add to cart
- **Cart thumbnail** — mockup replaces product image in cart
- **Order persistence** — original upload + composite mockup stored per line item
- **Customer order views** — mockup on Thank you page, View order, and My Account orders list
- **Order emails** — mockup preview in HTML confirmation emails
- **Admin orders list** — Design column with thumbnail for personalized orders
- **Settings** — upload size, recommended DPI, low-resolution warnings
- **Admin ZIP export** — download all design files for an order in one click
- **Compare preview** — toggle plain shirt vs personalized mockup on the product page
- **Dashboard widget** — recent personalized orders at a glance
- **Admin order detail** — mockup preview + secure download of original file
- **HPOS compatible** — works with WooCommerce High-Performance Order Storage

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+ with **GD** extension

## Installation

1. Clone or download into `wp-content/plugins/woo-personalization`
2. Activate **Woo Personalization** in Plugins
3. Go to **WooCommerce → Mockup Templates** and create a template (base image + print area)
4. Edit a product → **Personalization** tab → enable and select template

## Quick start

```
WooCommerce → Mockup Templates → Add New
  → Upload base mockup image
  → Drag print area on preview

Products → Edit → Personalization tab
  → Enable personalization
  → Select template

Storefront → product page → customer uploads design → checkout
```

## Development

```bash
# Syntax check (same as CI)
find includes -name '*.php' -print0 | xargs -0 -n1 php -l
```

## License

GPLv2 or later. See [readme.txt](readme.txt).
