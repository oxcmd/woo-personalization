# Woo Personalization

[![PHP Lint](https://github.com/oxcmd/woo-personalization/actions/workflows/php-lint.yml/badge.svg)](https://github.com/oxcmd/woo-personalization/actions/workflows/php-lint.yml)
[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](LICENSE)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-96588a)](https://woocommerce.com/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)](https://wordpress.org/)

Open-source **WooCommerce plugin** for apparel personalization: customers upload a design, preview it on a mockup, and production files are stored on the order.

**Maintainer:** [@oxcmd](https://github.com/oxcmd)

## Why this project

Print-on-demand and custom apparel shops need a lightweight alternative to heavy product customizers. Woo Personalization focuses on:

- Fast mockup preview on the product page
- Secure file handoff from cart → order → admin production
- WooCommerce HPOS compatibility out of the box
- No external SaaS dependency

## Features

| Area | Capability |
|------|------------|
| Storefront | Upload UI, live mockup, plain vs design compare, DPI warning |
| Cart / checkout | Custom cart thumbnail, line item meta, block checkout support |
| Customer account | Order mockups, My Account orders Design column |
| Emails | Mockup preview in HTML order emails |
| Admin | Order mockups, secure downloads, ZIP export, orders list Design column |
| Operations | Settings tab, system status checks, dashboard widget |

See [CHANGELOG.md](CHANGELOG.md) for release history.

## Quick start

```bash
git clone https://github.com/oxcmd/woo-personalization.git
cp -R woo-personalization /path/to/wordpress/wp-content/plugins/woo-personalization
```

Then in WordPress admin:

1. Activate **Woo Personalization** (WooCommerce required)
2. **WooCommerce → Mockup Templates** → create template + print area
3. Edit a product → **Personalization** tab → enable + select template

Detailed docs: [woo-personalization/README.md](woo-personalization/README.md)

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+ with **GD** (ZipArchive optional, for admin ZIP export)

## Repository layout

```
woo-personalization/     # Plugin source (install this folder into wp-content/plugins)
docs/                    # Architecture & maintainer docs
.github/                 # CI, issue/PR templates
AGENTS.md                # Guide for AI coding agents (Cursor, Codex, Copilot)
CONTRIBUTING.md          # How to contribute
```

## Contributing

Issues and pull requests are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a PR.

AI-assisted contributions are encouraged — start with [AGENTS.md](AGENTS.md).

## Security

Report vulnerabilities privately. See [SECURITY.md](SECURITY.md).

## License

GPLv2 or later. See [LICENSE](LICENSE).
