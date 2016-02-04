=== WooCommerce Sequential Order Numbers ===
Contributors: SkyVerge, maxrice, tamarazuk, chasewiseman, nekojira
Tags: woocommerce, order number
Requires at least: 4.0
Tested up to: 4.4.2
Requires WooCommerce at least: 2.3
Tested WooCommerce up to: 2.5
Stable tag: 1.6.1

This plugin extends WooCommerce by setting sequential order numbers for new orders.

== Description ==

This plugin extends WooCommerce by automatically setting sequential order numbers for new orders.  If there are existing orders at the time of installation, the sequential order numbers will start with the highest current order number.

**This plugin requires WooCommerce 2.1 or newer.**

> No configuration needed! The plugin is so easy to use, there aren't even any settings. Activate it, and orders will automatically become sequential.

If you like this plugin, but are looking for the ability to set the starting number, or to add a custom prefix/suffix to your order numbers (ie, you'd prefer something like WT101UK, WT102UK, etc) please consider our premium WooCommerce Sequential Order Numbers Pro plugin, which is available in the [WooThemes Store](http://www.woothemes.com/products/sequential-order-numbers-pro/).

= Feedback =
* We are open to your suggestions and feedback - Thank you for using or trying out one of our plugins!
* Drop us a line at [www.skyverge.com](http://www.skyverge.com)

= Support Details =
We do support our free plugins and extensions, but please understand that support for premium products takes priority. We typically check the forums every few days (with a maximum delay of one week).

= More Details =
 - See the [product page](http://www.skyverge.com/product/woocommerce-sequential-order-numbers/) for full details.
 - Check out the [Pro Version](http://www.woothemes.com/products/sequential-order-numbers-pro/).
 - View more of SkyVerge's [free WooCommerce extensions](http://profiles.wordpress.org/skyverge/)
 - View all [SkyVerge WooCommerce extensions](http://www.skyverge.com/shop/)

== Installation ==

1. Upload the entire 'woocommerce-sequential-order-numbers' folder to the '/wp-content/plugins/' directory or upload the zip via Plugins &gt; Add New
2. Activate the plugin through the 'Plugins' menu in WordPress
3. No configuration needed! Order numbers will continue sequentially from the current highest order number, or from 1 if no orders have been placed yet

== Frequently Asked Questions ==

= Where are the settings? =

The plugin doesn't require any :). When you activate it, it gets to work right away! Orders will automatically become sequential, starting from the most recent order number.

= Can I start the order numbers at a particular number? =

This free version does not have that functionality, but the premium [WooCommerce Sequential Order Numbers Pro](http://www.woothemes.com/products/sequential-order-numbers-pro/) will allow you to choose any starting number that's higher than your most current order number.

= Can I set an order number prefix/suffix? =

This free version does not have that functionality, but it's included in the premium [WooCommerce Sequential Order Numbers Pro](http://www.woothemes.com/products/sequential-order-numbers-pro/).

== Changelog ==

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
