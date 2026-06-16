=== Woo Personalization ===
Contributors: dailybuilder
Tags: woocommerce, personalization, t-shirt, mockup, custom product
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
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

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/woo-personalization`
2. Activate through the Plugins screen
3. Ensure WooCommerce is active
4. Create a mockup template under WooCommerce → Mockup Templates
5. Enable personalization on a product in the Personalization tab

== Frequently Asked Questions ==

= Does this require GD? =

Yes. PHP GD is used to composite customer uploads onto mockup images.

= Is HPOS supported? =

Yes. The plugin declares compatibility with WooCommerce custom order tables.

== Changelog ==

= 1.0.0 =
* Initial release
