# REST API

Woo Personalization extends the WooCommerce REST API for external fulfillment systems.

## Authentication

Use standard WooCommerce REST API credentials (`manage_woocommerce` or read orders capability).

Base URL: `/wp-json/wc/v3/orders/{id}`

## Order fields

Each order response includes:

```json
{
  "wcp_personalization": {
    "has_personalized_items": true,
    "personalized_item_count": 2
  }
}
```

## Line item fields

Personalized line items include:

```json
{
  "wcp_personalization": {
    "personalized": true,
    "template_id": 16,
    "mockup_url": "https://example.com/wp-content/uploads/wcp-uploads/.../mockup.png",
    "has_original": true,
    "has_mockup": true
  }
}
```

## Example

```bash
curl -u ck_xxx:cs_xxx \
  "https://your-store.test/wp-json/wc/v3/orders/17"
```

Download original files via the WordPress admin (secure nonce URLs) or the admin ZIP export until a dedicated download API is added.

## Related

- [Architecture](ARCHITECTURE.md)
- [ROADMAP](../ROADMAP.md)
