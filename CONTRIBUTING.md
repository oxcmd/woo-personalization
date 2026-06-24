# Contributing to Woo Personalization

Thank you for helping improve Woo Personalization. This project is maintained by [@oxcmd](https://github.com/oxcmd).

## Ways to contribute

- **Bug reports** — use the [bug report template](.github/ISSUE_TEMPLATE/bug_report.yml)
- **Feature ideas** — use the [feature request template](.github/ISSUE_TEMPLATE/feature_request.yml)
- **Pull requests** — bug fixes, docs, and focused features
- **Reviews** — comment on open PRs and issues

## Before you start

1. Search [existing issues](https://github.com/oxcmd/woo-personalization/issues) to avoid duplicates
2. Read [AGENTS.md](AGENTS.md) if you use an AI coding agent
3. Read [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for module overview

## Development setup

```bash
git clone https://github.com/oxcmd/woo-personalization.git
cd woo-personalization
# Copy plugin folder into your local WordPress:
cp -R woo-personalization /path/to/wp-content/plugins/woo-personalization
```

Requirements: WordPress 6.0+, WooCommerce 7.0+, PHP 7.4+ with GD.

## Coding standards

- Follow existing `WCP_*` class structure in `woo-personalization/includes/`
- Prefix hooks/meta: `wcp_`, `_wcp_`
- Escape output (`esc_html`, `esc_url`, `wp_kses_post` as appropriate)
- Sanitize input (`absint`, `sanitize_key`, `sanitize_file_name`)
- Keep PRs focused — one concern per PR when possible

## Pull request process

1. Fork the repo and create a branch from `main`
2. Make changes in `woo-personalization/` (plugin source)
3. Run PHP lint:

   ```bash
   find woo-personalization/includes woo-personalization/woo-personalization.php -name '*.php' -print0 | xargs -0 -n1 php -l
   ```

4. Update `CHANGELOG.md` under **Unreleased** for user-visible changes
5. Open a PR with a clear description and test steps
6. Maintainer will review, request changes, or merge

## Issue triage labels (planned)

| Label | Meaning |
|-------|---------|
| `bug` | Confirmed defect |
| `enhancement` | Feature request |
| `good first issue` | Small, well-scoped starter task |
| `help wanted` | Maintainer welcomes community PR |

## Releases

Releases follow [Semantic Versioning](https://semver.org/). See [CHANGELOG.md](CHANGELOG.md).

## Code of conduct

Be respectful and constructive. Harassment or spam will not be tolerated.

## Questions

Open a [GitHub Discussion](https://github.com/oxcmd/woo-personalization/discussions) or an issue with the `question` label.
