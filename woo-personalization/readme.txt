=== Woo Personalization ===
Contributors: dailybuilder
Tags: woocommerce, personalization, t-shirt, mockup, custom product
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let WooCommerce customers upload a design, preview it on a t-shirt mockup, and attach files to orders.

== Description ==

Woo Personalization adds product-level t-shirt customization for WooCommerce:

* Mockup template library with configurable print areas
* Per-product template assignment and optional print area override
* Customer image upload with live mockup preview
* Composite mockup and original upload stored on order line items
* Admin order view with mockup thumbnail and secure download
* Customer order pages and emails with mockup preview
* Admin and My Account orders list Design column

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/woo-personalization`
2. Activate through the Plugins screen
3. Ensure WooCommerce is active
4. Create a mockup template under WooCommerce → Mockup Templates
5. Enable personalization on a product in the Personalization tab

== Frequently Asked Questions ==

= Does this require GD? =

Yes. PHP GD is used to composite customer uploads onto mockup images.

= What about ZIP downloads? =

Admin order ZIP export requires the PHP ZipArchive extension.

= Is HPOS supported? =

Yes. The plugin declares compatibility with WooCommerce custom order tables.

== Changelog ==

= 1.5.0 =
* Drag-and-drop design positioning within the print area
* Scale slider (50%-200%) with live server-side mockup re-composite

= 1.4.1 =
* Fix REST API line item fields, Blocks cart images, admin filter backfill, duplicate production notes, email mockup context

= 1.4.0 =
* REST API fields on orders and line items for fulfillment integrations
* WooCommerce Blocks cart mockup image parity
* Admin orders filter: personalized orders only
* Private production order notes on checkout

= 1.3.0 =
* Plain shirt / Your design compare toggle on the product preview
* WordPress dashboard widget for recent personalized orders
* WooCommerce System Status health checks for the plugin

= 1.2.0 =
* WooCommerce Settings → Personalization tab (upload limit, DPI threshold)
* Low-resolution upload warning on the product page
* Admin one-click ZIP download for all order personalization files

= 1.1.0 =
* My Account orders list Design column with mockup thumbnail
* Mockup preview in WooCommerce order confirmation emails
* README and GitHub Actions PHP lint workflow

= 1.0.0 =
* Initial release
