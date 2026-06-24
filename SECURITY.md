# Security Policy

## Supported versions

| Version | Supported |
|---------|-----------|
| 1.3.x   | Yes       |
| < 1.3   | Best effort |

## Reporting a vulnerability

**Please do not open public GitHub issues for security vulnerabilities.**

Email or DM the maintainer [@oxcmd](https://github.com/oxcmd) with:

- Description of the issue
- Steps to reproduce
- Impact assessment (if known)

You should receive a response within 7 days. We will coordinate a fix and credit reporters when appropriate.

## Scope

In scope:

- Unauthorized file access or download bypass in `woo-personalization/`
- Upload validation bypass (malicious files, path traversal)
- Privilege escalation via plugin hooks

Out of scope:

- WordPress core or WooCommerce core vulnerabilities
- Server misconfiguration unrelated to this plugin
