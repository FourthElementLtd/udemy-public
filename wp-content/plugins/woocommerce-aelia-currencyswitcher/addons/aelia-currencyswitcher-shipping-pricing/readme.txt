=== WooCommerce Currency Switcher - Shipping Pricing ===
Tags: woocommerce, currency switcher, shipping, manual pricing, shipping pricing
Requires at least: 4.0
Tested up to: 6.0.9
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Extends the [Aelia Currency Switcher](https://bit.ly/WC_AFC_S3) plugin, by allowing to manually specify prices for the various shipping options.

== Description ==
This improves the [Aelia Currency Switcher](https://bit.ly/WC_AFC_S3) plugin, by adding the possibility of specifying shipping prices manually, for each currency, rather than having them calculated on the fly using exchange rates.

== Requirements ==

* PHP 7.1 or higher.
* WordPress 4.0 or newer.
* WooCommerce 3.0 or newer.
* Aelia Currency Switcher 4.7.0.190307 or later
* [AFC plugin for WooCommerce](https://bit.ly/WC_AFC_S3) 2.0.1.180821 or later.

== Installation ==
1. Extract the zip file and drop the contents in the wp-content/plugins/ directory of your WordPress installation.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to WooCommerce > Settings > Shipping to configure the shipping costs in each currency.

== Changelog ==

= 1.4.17.220704 =
* Updated supported WooCommerce versions.

= 1.4.16.220607 =
* Updated supported WooCommerce versions.

= 1.4.15.220502 =
* Updated supported WooCommerce versions.
* Updated supported WordPress versions.

= 1.4.14.220330 =
* Updated supported WooCommerce versions.

= 1.4.13.220224 =
* Updated supported WooCommerce versions.

= 1.4.12.220124 =
* Updated supported WooCommerce versions.

= 1.4.11.220122 =
* Tweak - Refactored logic used to track the currency stored against shipping rates.

= 1.4.10.220104 =
* Updated supported WooCommerce versions.

= 1.4.9.211208 =
* Updated supported WooCommerce versions.
* Updated supported WordPress versions.

= 1.4.8.211102 =
* Updated supported WooCommerce versions.

= 1.4.7.211005 =
* Updated supported WooCommerce versions.

= 1.4.6.210906 =
* Updated supported WooCommerce versions.

= 1.4.5.210816 =
* Updated supported WooCommerce versions.
* Updated supported WordPress versions.

= 1.4.4.210624 =
* Fix - Fixed logic used to fetch the active currency after adding a new shipping method to a zone.
* Fix - Added check to prevent loading base currency settings for a shipping method that has just been added.
* Tweak - Improved Admin User Interface. Rewritten the label and description of the "enable currency specific settings" for the shipping methods, to better describe its purpose.

= 1.4.3.210623 =
* Tweak - Added check to prevent loading the Shipping Pricing Addon multiple times.

= 1.4.2.210622 =
* Updated supported WooCommerce versions.
* Updated supported WordPress versions.

= 1.4.1.210622 =
* Updated supported WooCommerce versions.

= 1.4.0.210517 =
* Refactor - Rewritten initialisation logic to allow embedding the Shipping Pricing Addon directly inside the Currency Switcher.
* Major rewrite - Replaced dynamically generated shipping classes with traits.

= 1.3.22.210513 =
* Updated supported WooCommerce versions.

= 1.3.21.210423 =
* Fix - Fixed links on the currency selector for the shipping methods. The links now point to the correct page after adding a new shipping method.

= 1.3.20.210406 =
* Updated supported WooCommerce and WordPress versions.

= 1.3.18.210128 =
* Updated supported WooCommerce versions.
* Updated supported WordPress versions.

= 1.3.17.201207 =
* Updated supported WooCommerce versions.

= 1.3.16.201103 =
* Updated supported WooCommerce versions.

= 1.3.15.200904 =
* Updated supported WooCommerce versions.

= 1.3.14.201005 =
* Updated supported WooCommerce versions.
* Updated supported WordPress versions.

= 1.3.13.200904 =
* Updated supported WooCommerce versions.

= 1.3.12.200813 =
* Updated supported WordPress and WooCommerce versions.

= 1.3.12.200625 =
* Updated supported WooCommerce versions.

= 1.3.12.200603 =
* Updated supported WooCommerce versions.

= 1.3.11.200428 =
* Updated requirement checking class.
* Updated requirements.
* Updated supported WooCommerce versions.

= 1.3.10.200320 =
* Fix - Fixed loading of currency-specific shipping costs with for BolderElements Table Rates Shipping plugin.
* Updated supported WooCommerce versions.

= 1.3.9.200225 =
* Tweak - Improved support for BolderElements Table Rates Shipping plugin.
* Updated supported WooCommerce versions.
* Updated requirements.

= 1.3.8.200122 =
* Updated supported WooCommerce versions.

= 1.3.7.191105 =
* Updated supported WooCommerce versions.

= 1.3.6.190920 =
* Updated supported WooCommerce versions.

= 1.3.5.190301 =
* Updated supported WooCommerce versions.
* Updated supported WordPress versions.

= 1.3.4.181015 =
* Updated supported WooCommerce versions.

= 1.3.3.180112 =
* Improvement - Added support for Table Rates Shipping 4.1.

= 1.3.2.171011 =
* Improvement - Added support for to the new Aelia auto-updates system.
* Tweak - Updated requirements.
* Improvement - Added supported WC versions to plugin header.

= 1.3.1.170606 =
* Fix - Backward compatibility with WooCommerce 2.4 and 2.5.

= 1.3.0.170510 =
* Improvement - Added support for Table Rates Shipping 4.0.x.

= 1.2.4.160822 =
* Fixed bug in user interface on the Shipping Zone page. The bug caused shipping zones to switch to the base currency after saving the settings of a shipping method.

= 1.2.3.160614 =
* Removed minor notice messages.

= 1.2.3.160516 =
* Improvement - Added support for the new "popup" interface for zone shipping methods.
* Improvement - Added automatic redirection to the original shipping method instance when changing currency.

= 1.2.2.160310 =
* Bug fix - Fixed calculation of currency specific shipping in WC 2.6. The calculation was broken by unexpected changes in how shipping methods are handled when they are assigned to a zone.

= 1.2.1.160310 =
* Bug fix - Fixed calculation of shipping in WC 2.5 and later. The bug caused the message "no shipping method available" to appear if shipping costs were entered for a currency, saved and the "manual costs" flag was disabled.
* Bug fix - Fixed handling of "manual prices enabled" flag in WC 2.6.
* Tweak - Excluded shipping method templates from autoloader's class maps, to reduce the possibility of conflicts.

= 1.2.0.x =
* Added compatibility with WC 2.6.
