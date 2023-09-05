=== Sequential Order Numbers for WooCommerce ===
Contributors: SkyVerge, maxrice, tamarazuk, chasewiseman, nekojira, beka.rice
Tags: woocommerce, order number, sequential order number, woocommerce orders
Requires at least: 5.6
Tested up to: 6.3.1
Requires PHP: 7.4
Stable tag: 1.10.1

This plugin extends WooCommerce by setting sequential order numbers for new orders.

== Description ==

This plugin extends WooCommerce by automatically setting sequential order numbers for new orders.  If there are existing orders at the time of installation, the sequential order numbers will start with the highest current order number.

**This plugin requires WooCommerce 3.9.4 or newer.**

> No configuration needed! The plugin is so easy to use, there aren't even any settings. Activate it, and orders will automatically become sequential.

If you have no orders in your store, your orders will begin counting from order number 1. If you have existing orders, the count will pick up from your highest order number.

If you've placed test orders, you must trash **and** permanently delete them to begin ordering at "1" (trashed orders have to be counted in case they're restored, so they need to be gone completely).

= Support Details =

We do support our free plugins and extensions, but please understand that support for premium products takes priority. We typically check the forums every few days (usually with a maximum delay of one week).

= Sequential Order Numbers Pro =

If you like this plugin, but are looking for the ability to set the starting number, or to add a custom prefix/suffix to your order numbers (ie, you'd prefer something like WT101UK, WT102UK, etc) please consider our premium Sequential Order Numbers Pro for WooCommerce plugin, which is available in the [WooCommerce Store](http://woocommerce.com/products/sequential-order-numbers-pro/).

= More Details =
 - See the [product page](http://www.skyverge.com/product/woocommerce-sequential-order-numbers/) for full details.
 - Check out the [Pro Version](http://woocommerce.com/products/sequential-order-numbers-pro/).
 - View more of SkyVerge's [free WooCommerce extensions](http://profiles.wordpress.org/skyverge/)
 - View all [SkyVerge WooCommerce extensions](http://www.skyverge.com/shop/)

Interested in contributing? You can [find the project on GitHub](https://github.com/skyverge/woocommerce-sequential-order-numbers) and contributions are welcome :)

== Installation ==

You can install the plugin in a few ways:

1. Upload the entire 'woocommerce-sequential-order-numbers' folder to the '/wp-content/plugins/' directory
2. Upload the zip file you download via Plugins &gt; Add New
3. Go to Plugins &gt; Add New and search for "Sequential Order Numbers for WooCommerce", and install the one from SkyVerge.

Once you've installed the plugin, to get started please:

1. Activate the plugin through the "Plugins" menu in WordPress.
2. No configuration needed! Order numbers will continue sequentially from the current highest order number, or from 1 if no orders have been placed yet.

== Frequently Asked Questions ==

= Where are the settings? =

The plugin doesn't require any :) When you activate it, it gets to work right away! Orders will automatically become sequential, starting from the most recent order number.

= Why doesn't my payment gateway use this number? =

For full compatibility with extensions which alter the order number, such as Sequential Order Numbers, WooCommerce extensions should use `$order->get_order_number();` rather than `$order->id` when referencing the order number.

If your extension is not displaying the correct order number, you can try contacting the developers of your payment gateway to see if it's possible to make this tiny change. Using the order number instead is both compatible with WooCommerce core and our plugin, as without the order number being changed, it will be equal to the order ID.

= Can I start the order numbers at a particular number? =

This free version does not have that functionality, but the premium [Sequential Order Numbers Pro for WooCommerce](http://www.woothemes.com/products/sequential-order-numbers-pro/) will allow you to choose any starting number that's higher than your most current order number.

= Can I start the order numbers at "1"? =

If you want to begin numbering at "1", you must trash, then permanently delete all orders in your store so that there are no order numbers already being counted.

= Can I set an order number prefix/suffix? =

This free version does not have that functionality, but it's included in the premium [Sequential Order Numbers Pro for WooCommerce](http://www.woothemes.com/products/sequential-order-numbers-pro/).

== Other Notes ==

If you'd like to make your payment gateway compatible with Sequential Order Numbers, or other plugins that filter the order number, please make one small change. Instead of referencing `$order->id` when storing order data, reference: `$order->get_order_number()`

This is compatible with WooCommerce core by default, as the order number is typically equal to the order ID. However, this will also let you be compatible with plugins such as ours, as the order number can be filtered (which is what we do to make it sequential), so using order number is preferred.

Some other notes to help developers:

= Get an order from order number =

If you want to access the order based on the sequential order number, you can do so with a helper method:

`
$order_id = wc_sequential_order_numbers()->find_order_by_order_number( $order_number );
`

This will give you the order's ID (post ID), and you can get the order object from this.

= Get the order number =

If you have access to the order ID or order object, you can easily get the sequential order number based on WooCommerce core functions.

`
$order = wc_get_order( $order_id );
$order_number = $order->get_order_number();
`

== Changelog ==

- 2023.09.05 - version 1.10.1 =
 * Fix - Call save order method only in HPOS installs to avoid setting the same order number meta twice in CPT installations

- 2023.08.02 - version 1.10.0 =
 * Tweak - Also set sequential order numbers for orders sent via the WooCommerce Checkout Block
 * Misc - Add compatibility for WooCommerce High Performance Order Storage (HPOS)
 * Misc - Require PHP 7.4 and WordPress 5.6

= 2022.07.30 - version 1.9.7 =
 * Misc - Rename to Sequential Order Numbers for WooCommerce

= 2022.03.01 - version 1.9.6 =
 * Misc - Require WooCommerce 3.9.4 or newer
 * Misc - Replace calls to deprecated `is_ajax()` with `wp_doing_ajax()`

= 2020.05.07 - version 1.9.5 =
 * Misc - Add support for WooCommerce 4.1

= 2020.03.10 - version 1.9.4 =
 * Misc - Add support for WooCommerce 4.0

= 2020.02.05 - version 1.9.3 =
 * Misc - Add support for WooCommerce 3.9

= 2019.11.05 - version 1.9.2 =
 * Misc - Add support for WooCommerce 3.8

= 2019.10.03 - version 1.9.1 =
 * Fix - Fix order number filter in WooCommerce Admin Downloads Analytics

= 2019.08.15 - version 1.9.0 =
* Misc - Add support for WooCommerce 3.7
* Misc - Remove support for WooCommerce 2.6

= 2018.07.17 - version 1.8.3 =
* Misc - Require WooCommerce 2.6.14+ and WordPress 4.4+

= 1.8.2 - 2017.08.22 =
* Fix - PHP deprecation warning when Subscriptions is used
* Misc - Removed support for WooCommerce Subscriptions older than v2.0

= 1.8.1 - 2017.03.28 =
* Fix - Removes errors on refund number display

= 1.8.0 - 2017.03.23 =
* Fix - Admin orderby was not properly scoped to orders, props [@brandondove](https://github.com/brandondove)
* Misc - Added support for WooCommerce 3.0
* Misc - Removed support for WooCommerce 2.4

= 1.7.0 - 2016.05.24 =
* Misc - Added support for WooCommerce 2.6
* Misc - Removed support for WooCommerce 2.3

= 1.6.1 - 2016.02.04 =
* Misc - WooCommerce Subscriptions: Use new hook wcs_renewal_order_meta_query instead of deprecated woocommerce_subscriptions_renewal_order_meta_query

= 1.6.0 - 2016.01.20 =
* Misc - WooCommerce Subscriptions: Use new filter hook wcs_renewal_order_created instead of deprecated woocommerce_subscriptions_renewal_order_created
* Misc - WooCommerce 2.5 compatibility
* Misc - Dropped WooCommerce 2.2 support

= 1.5.1 - 2015.11.26 =
* Fix - Compatibility fix with WooCommerce Subscriptions 2.0

= 1.5.0 - 2015.07.28 =
* Misc - WooCommerce 2.4 Compatibility

= 1.4.0 - 2015.02.10 =
* Fix - Improved install routine for shops with a large number of orders
* Misc - WooCommerce 2.3 compatibility

= 1.3.4 - 2014.09.23 =
* Fix - Compatibility fix with WooCommerce 2.1
* Fix - Fix a deprecated notice in WooCommerce 2.2

= 1.3.3 - 2014.09.05 =
* Localization - Included a .pot file for localization

= 1.3.2 - 2014.09.02 =
* Misc - WooCommerce 2.2 compatibility

= 1.3.1 - 2014.01.22 =
* Misc - WooCommerce 2.1 compatibility

= 1.3 - 2013.04.26 =
* Feature - Improved WooCommerce Subscriptions compatibility
* Feature - Improved WooCommerce Pre-Orders compatibility
* General code cleanup and refactor

= 1.2.4 - 2012.12.14 =
* Fix - WordPress 3.5 compatibility fix
* Fix - Order numbers not assigned to temporary auto-draft orders created from the admin

= 1.2.3 - 2012.06.06 =
* Fix - Removed WooCommerce functions, which caused a compatibility issue with other WooCommerce plugins

= 1.2.2 - 2012.05.25 =
* Tweak - Takes advantage of new action hooks/filters available in WooCommerce 1.5.6
* Fix - Bug fix on installation to stores with more than 10 existing orders

= 1.2.1 - 2012.05.13 =
* Tweak - Minor updates due to WooCommerce 1.5.5 release

= 1.2.0 - 2012.04.21 =
* Feature - Added support for the order tracking page

= 1.1.2 - 2012.04.18 =
* Tweak - Minor updates due to WooCommerce 1.5.4 release

= 1.1.1 - 2012.04.02 =
* Fix - Order number in the subject line of the admin new order email is fixed

= 1.1.0 - 2012.04.02 =
* Feature - Search by order number

= 1.0.1 - 2012.04.02 =
* Fix - small bug fix

= 1.0.0 - 2012.04.02 =
* Initial Release

== Upgrade Notice ==

= 1.2.2 - 2012.05.25 =
This version requires WooCommerce 1.5.6

= 1.2.1 - 2012.05.13 =
This version requires WooCommerce 1.5.5
