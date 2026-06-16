# HeraSpec Project — Woo Personalization

## ⚡ Slash Commands (Agent Triggers)

When the user types messages starting with a slash (/), you MUST intercept them as action triggers rather than conversational queries. Please execute the corresponding terminal commands immediately:

- /heraspec [args] : Run the heraspec CLI tool with the provided arguments (e.g. /heraspec validate my-change)
- /sync : Run heraspec sync <current-change>
- /archive : Run heraspec archive <current-change> --yes
- /skill [name] : Run heraspec skill add [name]
- /hotfix [title] : Run heraspec hotfix [title]

*If the user types a slash command, do not explain what you are going to do, just run the terminal command and report the output.*

## Overview

WordPress plugin `woo-personalization` for WooCommerce. Customers upload an image on the product page, preview it on a t-shirt mockup (fixed print area), and place orders. Shop admins see the composite mockup and original upload in order management.

## Architecture

```
woo-personalization/
├── woo-personalization.php          # Bootstrap, constants, autoload
├── includes/
│   ├── class-plugin.php             # Orchestrator
│   ├── class-template-cpt.php       # Mockup template library (CPT)
│   ├── class-product-settings.php   # WC product data tab
│   ├── class-frontend.php           # Product page UI
│   ├── class-upload-handler.php     # AJAX upload + validation
│   ├── class-image-compositor.php   # Server-side PNG composite
│   ├── class-cart-order.php         # Cart/order line item meta
│   ├── class-admin-order.php        # Order admin display + download
│   └── class-cleanup.php            # Temp file cron cleanup
├── assets/js/product-personalizer.js
├── assets/css/product-personalizer.css
└── templates/product-personalizer.php
```

**Data flow:** Admin creates mockup template → assigns to product → customer uploads → AJAX validates & composites → cart meta → order meta → files moved to `uploads/wcp-orders/{order_id}/`.

## Key Dependencies

- WordPress 6.x
- WooCommerce 8.x+ (HPOS compatible)
- PHP GD extension (Imagick optional fallback)
- jQuery (bundled with WP admin/frontend)

## Conventions

- Prefix: `WCP_` for classes, `wcp_` for functions/hooks, `_wcp_` for meta keys
- Text domain: `woo-personalization`
- PHP: WordPress Coding Standards, strict types where practical, sanitize/escape all I/O
- No direct file URLs for customer uploads in orders — use secure download handler
- Print area stored as percentage `{x,y,width,height}` of base mockup image
