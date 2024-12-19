=== CZL Express for WooCommerce ===
Contributors: czlexpress
Tags: woocommerce, shipping, czl express, tracking, delivery, shipping method, china shipping, international shipping
Requires at least: 5.8
Tested up to: 6.4.2
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate CZL Express shipping service with your WooCommerce store. Get real-time shipping rates, create shipments, and track packages.

== Description ==

CZL Express for WooCommerce provides seamless integration between your WooCommerce store and CZL Express shipping service. This plugin helps you automate your shipping process and provide better service to your customers.

= Key Features =
* Real-time shipping rates calculation
* Automatic shipment creation
* Package tracking integration
* Custom product grouping for shipping rates
* Support for multiple currencies (CNY to other currencies)
* Automatic order status updates
* Customer-facing tracking information
* Full HPOS (High-Performance Order Storage) support
* WooCommerce remote logging support
* Multi-language support

= Advanced Features =
* Custom product grouping for different shipping methods
* Flexible pricing rules with markup support (e.g., "10% + 10")
* Quick order creation in CZL Express system
* Label printing support
* Automatic tracking number synchronization (every 30 minutes)
* Automatic tracking information updates (hourly)
* Customer-visible shipping updates

= Requirements =
* WordPress 6.0 or higher
* WooCommerce 6.0.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher
* CZL Express account and API credentials

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-czlexpress` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to the plugin's settings page and configure your CZL Express API credentials and exchange rate.
4. In WooCommerce > Settings > Shipping, configure your shipping zones:
   * Create a new zone
   * Select the desired regions
   * Add "CZL Express" as a shipping method
   * Configure the shipping method settings
5. Go to "CZL Express" > "Product Groups" to set up your product groups:
   * Remove default groups if needed
   * Add custom groups (e.g., "SF Line", "SF Small Packet")
6. The shipping rates will automatically calculate when customers enter their address.

== Frequently Asked Questions ==

= Do I need a CZL Express account? =

Yes, you need a CZL Express account and API credentials to use this plugin. Visit [https://exp.czl.net](https://exp.czl.net) to create an account.

= How do I get API credentials? =

Contact CZL Express support to obtain your API credentials.

= Can I test the plugin before going live? =

Yes, the plugin includes a test mode that allows you to test the integration without creating real shipments.

= How are shipping rates calculated? =

Shipping rates are calculated in real-time based on:
* Package weight and dimensions
* Destination address
* Selected shipping method
* Product grouping settings

= Can I add a markup to shipping rates? =

Yes, you can add both percentage and fixed amount markups. For example:
* "10%" - Add 10% to the base rate
* "+ 10" - Add 10 to the base rate
* "10% + 10" - Add 10% plus 10 to the base rate

= How often is tracking information updated? =

* Tracking numbers are synced every 30 minutes
* Tracking details are updated hourly
* Manual updates are also available

= Does it support multiple currencies? =

Yes, rates are fetched in CNY and automatically converted to your store's currency using the configured exchange rate.

== Screenshots ==

1. Plugin settings page - Configure API credentials and general settings
2. Shipping method configuration - Set up shipping zones and methods
3. Product group management - Create and manage product groups
4. Order with tracking information - View tracking details in orders
5. Shipping rate display - How customers see shipping options
6. Order management - Create shipments and print labels
7. Tracking information - Customer-facing tracking updates

== Changelog ==

= 1.0.0 =
* Initial release
* Full WooCommerce HPOS support
* Real-time shipping rates
* Automatic shipment creation
* Package tracking integration
* Product grouping support
* Multi-currency support
* Automatic tracking updates

== Upgrade Notice ==

= 1.0.0 =
Initial release of CZL Express for WooCommerce.

== Support ==

For support, please visit [https://exp.czl.net](https://exp.czl.net) or contact our support team. 