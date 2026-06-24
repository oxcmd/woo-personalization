# AGENTS.md — AI coding agent guide

Instructions for **Cursor**, **OpenAI Codex**, **GitHub Copilot**, and other AI agents working on this repository.

## Project summary

**Woo Personalization** is a WordPress/WooCommerce plugin (`woo-personalization/`) that lets customers upload images, preview composites on mockup templates, and attach files to orders.

- **Language:** PHP 7.4+, vanilla JS (jQuery on storefront), CSS
- **Prefix:** `WCP_` classes, `wcp_` functions, `_wcp_` order meta keys
- **Text domain:** `woo-personalization`

## Repository map

| Path | Purpose |
|------|---------|
| `woo-personalization/woo-personalization.php` | Plugin bootstrap |
| `woo-personalization/includes/` | PHP classes (`class-*.php`) |
| `woo-personalization/templates/` | Admin + storefront templates |
| `woo-personalization/assets/` | JS/CSS |
| `.github/workflows/php-lint.yml` | CI: `php -l` on plugin files |
| `docs/ARCHITECTURE.md` | Module diagram & data flow |

Ignore `heraspec/` unless the user explicitly asks for spec workflow changes.

## Local development

1. Symlink or copy `woo-personalization/` into `wp-content/plugins/woo-personalization`
2. WordPress + WooCommerce must be active
3. PHP **GD** extension required for image compositing

```bash
# Lint (same as CI)
find woo-personalization/includes woo-personalization/woo-personalization.php -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Coding rules

1. **Minimize scope** — match existing patterns; no drive-by refactors
2. **HPOS** — use HPOS-compatible order hooks when touching admin order lists
3. **Security** — sanitize uploads; admin downloads via nonce + `manage_woocommerce`
4. **No secrets** — never commit `.env`, credentials, or `wordpress/` local site
5. **i18n** — wrap user-facing strings in `__()` / `esc_html__()` with `woo-personalization`
6. **Commits** — only when the user asks; use author email `246876863+oxcmd@users.noreply.github.com` for @oxcmd

## Common tasks

### Add a storefront feature
- Hook in `includes/class-frontend.php` or new `class-frontend-*.php`
- Register in `includes/class-plugin.php`
- Enqueue assets in the relevant `enqueue_*` method

### Add admin feature
- Follow `class-admin-order.php` / `class-admin-orders-list.php` patterns
- Check HPOS vs legacy order screen IDs

### Order file paths
- Use `WCP_Cart_Order::resolve_item_file_paths()` before reading files
- Temp uploads: `wcp-uploads/wcp-temp/{token}/`
- Permanent: `wcp-uploads/wcp-orders/{order_id}/item-{item_id}/`

## What agents should NOT do

- Publish to WordPress.org without explicit approval
- Force-push `main` without user confirmation
- Modify live customer sites without approval
- Add heavy frameworks (React build, Composer) unless requested
- Commit the local `wordpress/` directory

## Pull request checklist

- [ ] PHP syntax passes lint
- [ ] HPOS + legacy order paths considered (if admin orders touched)
- [ ] User-facing strings translatable
- [ ] `readme.txt` + `CHANGELOG.md` updated for user-visible changes
- [ ] No unrelated files in the diff

## Maintainer

[@oxcmd](https://github.com/oxcmd) — triages issues, reviews PRs, cuts releases from `CHANGELOG.md`.
