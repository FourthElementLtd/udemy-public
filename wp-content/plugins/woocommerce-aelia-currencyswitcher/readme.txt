=== Aelia Currency Switcher for WooCommerce ===
Tags: aelia, woocommerce, currency switcher, multiple currencies
Requires at least: 3.6
Tested up to: 6.0.9
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Aelia Currency Switcher for WooCommerce allows your shop to display prices and accept payments in multiple currencies. This will grant your customers the possibility of shopping in their favourite currency, thus increasing conversions.

== Description ==

The Aelia Currency Switcher will allow you to configure a list of the currencies you would like to accept. Such currencies will then appear in a list, displayed as a widget, which your Users can use to choose their preferred currency. When a customer selects a currency, the shop will be both displaying prices and completing transactions in the new currency. The prices displayed on the shop will be the ones that the customer will pay upon completing the order.

Increase conversion by cutting credit card fees
Credit Card operators often charge a conversion fee when a payment is made in a currency different from the one for which the card was issued. This adds an extra cost on every purchase, and it can discourage prospective customers. Giving your Visitors the possibility of paying in their currency can help improving conversion.

Every order will store the currency used to place it, so that both Shop Managers and customers will be able to retrieve it and see how much they paid.

*Important*: Your ability to accept payment in each currency will depend on your payment gateway and/or payment processing company.

= Acknowledgements =
* The Aelia Currency Switcher for WooCommerce includes GeoLite data created by MaxMind, available from https://www.maxmind.com.
* The Aelia Currency Switcher for WooCommerce includes <div>icons made by <a href="https://www.freepik.com" title="Freepik">Freepik</a> from <a href="https://www.flaticon.com/" title="Flaticon">www.flaticon.com</a></div>

== Requirements ==

* WordPress 4.0 or newer.
* PHP 7.1 or newer.
* WooCommerce 3.0.x or newer.
* **Free** [Aelia Foundation Classes framework](https://aelia.co/downloads/wc-aelia-foundation-classes.zip) 2.1.9.210525 or newer (the plugin can install the framework automatically).

== Installation ==

1. Extract the zip file and drop the contents in the wp-content/plugins/ directory of your WordPress installation.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Follow the instructions in our knowledge base to configure the Currency Switcher: [Aelia Currency Switcher - Getting Started](https://aelia.freshdesk.com/solution/articles/3000063641-aelia-currency-switcher-getting-started).

== Support ==
The Currency Switcher is backed by a top class support service, as well as a knowledge base to help you getting the best out of it. You can find them both here: [Currency Switcher - Support Portal](https://aelia.freshdesk.com/support/solutions/120257).

== Upgrade Notice ==

= 4.9.7 =
This version includes an optimised logic to handle the currency selection. Should you encounter any issues, please feel free to [contact our Support Team](https://aelia.freshdesk.com/support/home), who will be happy to assist you.

== Changelog ==

= 4.13.12.220704 =
* Updated supported WooCommerce versions.

= 4.13.11.220615 =
* Fix - Fixed bug that caused the "flags" country selector to show a broken image when the detected country wasn't in the list of allowed countries.

= 4.13.10.220604 =
* Updated supported WooCommerce versions.

= 4.13.9.220519 =
* Fix - Fixed bug that caused the "flags" currency selector widget to show broken images when a country whose currency was enabled was assigned to a different currency.

= 4.13.8.220502 =
* Updated supported WordPress versions.

= 4.13.7.220501 =
* Fix - Fixed bug related to manual orders. The bug  caused the duplication of order item meta after clicking on the "recalculate" button.

= 4.13.6.220421 =
* Updated currency code for Mauritanian ouguiya.
* Updated JS dependencis (@wordpress/scripts).
* Updated supported WooCommerce versions.

= 4.13.5.220330 =
* Updated supported WooCommerce versions.

= 4.13.4.220315 =
* Tweak - Improved support for WooCommerce Blocks > Checkout block. The block can now trigger the "force currency by country" option and update the currency when the shipping or billing country change.

= 4.13.3.220224 =
* Updated supported WooCommerce versions.

= 4.13.2.220131 =
* Fixed - Fixed recalculation of cart totals in Elementor's minicart and off-canvas cart. The new logic uses event `woocommerce_before_mini_cart_contents` for the recalculation, instead of `woocommerce_before_mini_cart`, as the latter is not triggered by Elementor.

= 4.13.1.220124 =
* Tweak - Added check to ensure that the currency set while adding a manual order is always a string. This fixes the compability issue with WooCommerce PayPal Payments (see https://wordpress.org/support/topic/manual-add-order-error).
* Updated supported WooCommerce versions.

= 4.13.0.220104 =
* Feature - Added settings page to implement a custom country/currency mapping.
* Updated localisation files.
* Updated supported WooCommerce versions.

= 4.12.13.211217 =
* Tweak - Removed legacy code used to determine if a product is on sale.
* Refactor - Replaced call to deprecated function `current_time()` with `time()`.

= 4.12.12.211208 =
* Updated supported WooCommerce versions.
* Updated supported WordPress versions.

= 4.12.11.211109 =
* Fix - Fixed rendering glitch of the dropdown currency selector with flags on Safari (MacOS).
* Fix - Fixed rendering glitch of the dropdown country selector with flags on Safari (MacOS).

= 4.12.10.211103 =
* Fix - Restored missing scripts used by the WooCommerce Admin integration.

= 4.12.9.211102 =
* Updated supported WooCommerce versions.

= 4.12.8.211005 =
* Updated supported WooCommerce versions.

= 4.12.7.210922 =
* Fix - Fixed HTML used to display the title of the widgets provided by the plugin, to ensure that the correct styling is applied automatically.

= 4.12.6.210825 =
* Fix - Fixed rendering of the currency selector widget used on New Order page, to display the currency selection button.
* Tweak - Removed Yahoo! Finance and WebServiceX from the available exchange rates providers. These services are no longer available.

= 4.12.5.210819 =
* Tweak - Modified the logic used to load the available payment gateways on the settings page. The new logic should also see "special" payment methods, such as the PayPal Credits Cards added by the WooCommerce Payments plugin.
* Feature - Added filter `wc_aelia_cs_settings_payment_gateway_available`. The filter allows 3rd parties to force a payment gateway to appear as available or unavailable in the backend, regardless of the result of the actual availability check.

= 4.12.4.210805 =
* Tweak - Added check for the presence of cart fragments to the frontend scripts. This will prevent errors caused by the fragment variable not being set.
* Fix - Fixed purging of cart fragments when using the "buttons" currency selector.
* Refactor - Rewritten settings page using the new Aelia settings API.
* Updated supported WooCommerce versions.

= 4.12.3.210711 =
* Compatibility - Improved compatibility with WordPress 5.8. Added better support for the new block widget editor.
* Updated requirements - The plugin now requires Aelia Foundation Classes 2.1.13.210706 or newer.

= 4.12.2.210706 =
* Feature - Added country selector widget with country flags.

= 4.12.1.210629 =
* Refactor - Rewritten currency selector widget scripts using TypeScript.

= 4.12.0.210629 =
* Feature - Added currency selector widget with country flags.
* Feature - Added currency display mode to currency selector widget. The widget now allows to choose if the currency code or the currency name should be displayed.
* Fix - Fixed currency formatting logic. Due to a typo, the currenc format settings were not taken into account in the latest update.

= 4.11.6.210623 =
* Addon - Updated bundled Shipping Pricing Addon.

= 4.11.5.210622 =
* Updated supported WooCommerce versions.
* Updated supported WordPress versions.

= 4.11.4.210622 =
* Addon - Updated bundled Shipping Pricing Addon.

= 4.11.3.210607 =
* Tweak - Added plugin icon for Freemius integration.

= 4.11.2.210531 =
* Feature - Added filter `wc_aelia_cs_get_product_price`. The filter allows to fetch the price of a product in a specific currency.

= 4.11.1.210520 =
* Added filter `wc_aelia_cs_converted_product_base_price`. The filter allows 3rd parties to alter the result of a conversion of a product's base price.
* Tweak - Updated settings page to include the Freemius contact form and account page.
* Updated requirements.
* Updated supported WooCommerce versions.

= 4.11.0.210517 =
* Tweak - Code refactoring. Reorganised the code to make it better organised and easier to maintain and expand.
* Feature - Added logic to bundle the Shipping Pricing Addon.

= 4.10.2.210513 =
* Updated supported WooCommerce versions.

= 4.10.1.210406 =
* Updated supported WooCommerce versions.

= 4.10.0.210312 =
* Feature - Added support for WooCommerce Checkout Block. Choosing a country from that block now updates customer's country and currency, when the "force currency by country" option is enabled.
* Tweak - Refactored WooCommerce Admin integration. Moved initialization of the integration to the `woocommerce_init` event.
* Tweak - Refactored logic used to convert shipping costs. The new logic now also looks for the shipping costs currency in the meta assigned to a shipping rate.
* Tweak - Improved logic used when the "force the currency by country" option is enabled. The new logic performs additional checks on the checkout page, to ensure that the currency matching the country selected by the customer is used to complete the order. This can improve compatibilities with plugins like Smart Coupons, which can trigger Ajax calls that can involuntarily affect the currency selection.

= 4.9.15.210311 =
* Updated supported WordPress and WooCommerce versions.

= 4.9.14.210215 =
* Fix - Fixed check of the nonce used by the shipping calculator on the cart page. The check is performed when the "force currency by country" option is set to "shipping country".

= 4.9.13.210128 =
* Updated supported WooCommerce versions.

= 4.9.12.210121 =
* Tweak - Refactored price conversion logic for grouped products.

= 4.9.11.210114 =
* Fix - Modified logic used to fetch the currency from current user's profile, so that is only applies to logged in users.
* Tweak - Moved filter `wc_aelia_cs_get_product_base_currency` to class `WC_Aelia_CurrencyPrices_Manager`.
* Feature - Added filter `wc_aelia_cs_get_product_base_price_in_currency`, to allow 3rd parties to fetch the base price of a product in a specific currency.
* Tweak - Rewritten and simplified price filter widget integration, for better compatibility with multiple WooCommerce versions.

= 4.9.10.210105 =
* Fix - Temporarily removed check on product's base currency on Edit Product page. The check could cause issues with variable products, which are going to be addressed.

= 4.9.9.210102 =
* Fix - Fixed currency selection logic. The "refresh" option now clears the selected currency, so that the selection can be performed again.
* Updated supported WooCommerce versions.
* Updated language files.

= 4.9.8.201228 =
* UI - Added check against the "product base currency" field on the Edit Product page, to warn the administrators when they set a base currency, but don't enter a price for it.
* Updated supported WooCommerce versions and requirements.

= 4.9.7.201227 =
* Fix - Fixed bug introduced in `4.9.5.201125` in the logic used to fetch customer's country. The bug could trigger a fatal error when option "force currency by country" was enabled at WooCommerce > Currency Switcher > Currency Selection.

= 4.9.6.201207 =
* Tweak - Improved consistency in the display of the exchange rates schedule.
* Updated supported WooCommerce versions.

= 4.9.5.201125 =
* Tweak - Refactored currency selection logic, to make it more consistent.
* Tweak - Optimised logic used to load currency format settings (decimal separator, thousand separator, currency symbol).

= 4.9.4.201118 =
* Feature - Added support for the new Analytics > Variations report introduced in WooCommerce 4.8.
* Tweak - Improved logic used to store exchange rates agaisnt refunds, when populating report data.
* Updated supported WordPress and WooCommerce versions.

= 4.9.3.201103 =
* Updated supported WooCommerce versions.

= 4.9.1.201005 =
* Updated supported WordPress versions.

= 4.9.0.200917 =
* Tweak - Improved WooCommerce Admin Analytics integration. Added option to generate reports with all data and totals in base currency, like in standard reports. **This feature requires WooCommerce 4.6 or later**.
* Tweak - Removed obsolete jQuery library `jquery.ba-bbq.min.js`.

= 4.8.15.200904 =
* Updated supported WooCommerce versions.

= 4.8.15.200902 =
* Tweak - Added Freemius deploy script.

= 4.8.14.200813 =
* Updated supported WordPress and WooCommerce versions.

= 4.8.14.200805 =
* Tweak - Improved WooCommerce Admin Analytics integration. Added currency filter to Dashboard report.
* Updated supported WooCommerce versions.

= 4.8.13.200617 =
* Feature - Added new filter `wc_aelia_cs_product_requires_conversion`.
* Added support for `currency` attribute stored against a shipping rate. This will make it easier to add multi-currency support to shipping plugins.
* Tweak - Updated admin UI to show that the log files now contain a timestamp in their name.
* Fix - Fixed check that caused a "zero decimals" setting to be ignored.
* Fix - Added trimming of exchange rates entered manually on the settings page.
* Updated supported WooCommerce versions.
= 4.8.7.200417 =
* Feature - Added logic to save the base currency exchange rate against each order and refund.
* Tweak - Improved WooCommerce Admin Analytics. Added logic to set the currency symbol and format dynamically.

= 4.8.12.200605 =
* Tweak - Code optimisations, to improve performance.

= 4.8.11.200524 =
* Tweak - Improved WooCommerce Admin Analytics integration. Added currency filter to Coupons report.

= 4.8.10.200518 =
* Tweak - Changed level of "exchange rate not found" message from ERROR to WARNING.
* Updated supported WooCommerce versions.

= 4.8.9.200506 =
* Tweak - Improved WooCommerce Admin Analytics integration. Added logic to decode HTML entities for currency symbols and price formats.

= 4.8.8.200428 =
* Updated requirement checking class.
* Updated requirements.
* Updated supported WooCommerce versions.

= 4.8.7.200427 =
* Tweak - Improved WooCommerce Admin Analytics integration. Added logic to set the currency symbol and format dynamically (requires WooCommerce Admin 1.1.0 or newer).

= 4.8.6.200408 =
* Updated supported WooCommerce versions.

= 4.8.5.200318 =
* Fix - Set default report currency to shop's base currency on WooCommerce Admin Analytics reports.

= 4.8.4.200316 =
* Tweak - Improved handling of cases when product prices are stored in an incorrect format.

= 4.8.3.200311 =
* Tweak - Added check to disable the WooCommerce Admin integration on WooCommerce 3.8 and earlier, where it can't run.

= 4.8.2.200310 =
* Feature - Added preliminary support for WooCommerce Admin Analytics reports.
* Updated supported WooCommerce and WordPress versions.

= 4.8.1.200207 =
* Tweak - Added logic to clear the currency-specific sale prices at the end of a scheduled sale.
* Updated supported WooCommerce versions.

= 4.8.0.200127 =
* Improved action `wc_aelia_cs_set_product_price`. It now allows to specify "regular_price" and "sale_price" to set the price in a specific currency for simple products and variations.
* Added new filter `wc_aelia_cs_bulk_edit_price_types_map`.

= 4.7.15.200114 =
* Updated supported WooCommerce versions.

= 4.7.14.191126 =
* Fix - Fixed minor bug that caused the time execution limit to be set to 10 minutes by the setup process, even after the setup was completed.
* Tweak - Improved integration with Turkey Central Bank, to skip invalid rates.

= 4.7.13.191105 =
* Updated supported WooCommerce versions.

= 4.7.12.191014 =
* Fix - Fixed call to filter `widget_title` in widget templates.
* Updated supported WooCommerce versions.

= 4.7.11.190924 =
* Improvement - Improved error checking during conversion of product prices, to detect and avoid invalid products.
* Tweak - Improved script to intercept the currency selection to handle widgets created dynamically, via JavaScript.

= 4.7.10.190830 =
* Updated supported WooCommerce versions.

= 4.7.9.190730 =
* Fix - Fixed condition that could cause regular prices to be overwritten during the scheduled processing of sale prices.

= 4.7.8.190709 =
* Fix - Fixed condition that caused order items to disappear during the creation of a manual order.

= 4.7.7.190706 =
* Fix - Restored original logic to store currency prices against product instances.
* Fix - Added workaround for bug caused by WC 3.6.3+, which caused product prices being overwritten by the "update product stock" function.
* Tweak - Increased number of decimals used to calculate orders' and items' totals in base currency.
* Tweak - Improved shortcode `aelia_cs_product_price`. Added support for sign up fees (subscriptions).
* Tweak - Improved shortcode `aelia_cs_currency_amount`. Added new filter `aelia_cs_pp_shortcode_converted_amount`, to allow 3rd parties to alter the output of the shortcode.

= 4.7.6.190703 =
* Tweak - Added workaround to prevent conflicts with other plugins while returning product prices in currency.

= 4.7.5.190628 =
* Tweak - Changed scope of method `WC_Aelia_CurrencySwitcher::get_currency_by_customer_country()` to "public".
* Tweak - Added check to show errors on the currency selector widget only to shop managers.

= 4.7.4.190619 =
* Fix - Fixed bug of product prices being overwritten by the "update product stock" function in WC 3.6.3 and newer.
* Tweak - Removed obsolete messages from Admin area.

= 4.7.3.190622 =
* Tweak - Removed obsolete admin messages.
* Updated supported WooCommerce versions.

= 4.7.3.190416 =
* Tweak - Optimised logic used to load currency specific prices for products.
* Updated supported WooCommerce versions.

= 4.7.2.190330 =
* Tweak - Added validation of product ID before trying to fetch its currency prices and perform a price conversion.

= 4.7.1.190322 =
* Tweak - Added check on frontend script, to handle the case where the `wc_cart_fragments_params` variable has been removed by disabling WooCommerce's cart fragments.

= 4.7.0.190307 =
* Feature - Implemented Bulk edit for simple and external products.
* Updated supported WooCommerce version.
* Updated supported WordPress version.

= 4.6.10.190301 =
* Updated supported WooCommerce version.
* Updated supported WordPress version.

= 4.6.9.181217 =
* Tweak - Rewritten logic to handle the price filter widget, removing legacy code.

= 4.6.8.181210 =
* Improved compatibility with WooCommerce 3.5.2.
* Updated supported WooCommerce version.
* Updated language files.

= 4.6.7.181124 =
* Fix - Updated "product is on sale" check, to take into account the time zone.
* Updated supported WooCommerce version.
* Updated supported WordPress version.

= 4.6.6.181004 =
* Updated supported WooCommerce versions.
* Fix - Modified currency selector widget to show warning about "force currency by country" option only on the frontend.

= 4.6.5.180828 =
* Feature - Added support for exchange rate markups expressed as a percentage (e.g. "10%").

= 4.6.4.180827 =
* Updated requirements. The plugin now requires Aelia Foundation Classed 2.0.1.180821 or newer.

= 4.6.3.180821 =
* Feature - Added order net total in base currency next to the order total, in Orders List page.
* Tweak - Changed minimum requirements to WooCommerce 2.6

= 4.6.2.180725 =
* Tweak - Implemented "lazy load" of exchange rates provider models. This is to allow 3rd parties to hook into the logic and add their own models.
* Tweak - Removed warnings when the price properties expected by the Currency Switcher are not found.

= 4.6.1.180716 =
* Fix - Fixed bug that caused the exchange rates settings to be lost after removing a currency.

= 4.6.0.180628 =
* Tweak - Implemented workaround to prevent the Memberships plugin from triggering notices during the conversion of product prices.
* Tweak - Added logic to ensure that shipping costs are calculated with the correct amount of decimals, before they are converted.

= 4.5.19.180608 =
* Fix - Set currency for Latvia to EUR.

= 4.5.18.180529 =
* Updated supported WooCommerce version.

= 4.5.18.180417 =
* Fix - Fixed logic used to save the order currency for manual orders.

= 4.5.17.180404 =
* Fix - Fixed display of variation prices on variable product pages.
* Fix - Fixed active currency when saving order meta.

= 4.5.16.180307 =
* Tweak - Removed redundant logger class and optimised logging logic.
* Fix - Fixed name of `<select>` field in the currency selector widget.
* Feature - Added new filter `wc_aelia_cs_force_currency_by_country`.

= 4.5.15.180222 =
* Tweak - Added new filter `wc_aelia_cs_load_order_edit_scripts`.

= 4.5.14.180122 =
* Fix - Removed notice with Grouped Products.
* Improvement - Added admin message to inform merchants that Yahoo! Finance has been discontinued.

= 4.5.13.180118 =
* Update - Discountinued Yahoo! Finance provider.
* Feature - Added interface with OFX exchange rates service.
* Feature - Added new filter `wc_aelia_cs_exchange_rates_models`.

= 4.5.12.171215 =
* Fix - Fixed bug that sometimes caused an infinite loop when processing refunds on WooCommerce 3.2.5 and newer.

= 4.5.11.171210 =
* Fix - Fixed logic used to collect refund data for reports.

= 4.5.10.171206 =
* Improvement - Improved performance of the logic used to handle variable products.

= 4.5.9.171204 =
* Tweak - Improved compatibility of geolocation logic with WooCommerce 3.2.x.

= 4.5.8.171127 =
* Fix - Fixed integration with BE Table Rates Shipping plugin, to ensure the conversion of "subtotal" thresholds.

= 4.5.7.171124 =
* Fix - Fixed "force currency by country" logic. The new logic makes sure that the "currency by country" takes priority over other selections.
* Improvement - Refactored logic used to show error messages related to the currency selector widget.
* Tweak - Added warning in the currency selector widget when the "force currency by country" option is enabled, to inform the site administrators that the manual currency selection has no effect.

= 4.5.6.171120 =
* Fix - Fixed pricing filter in WooCommerce 3.2.4. The filter range was no longer converted, due to an undocumented breaking change in WooCommerce.

= 4.5.5.171114 =
* Tweak - Added check to prevent the "force currency by country" option from interfering with the manual creation of orders.
* Tweak - Added possibility to specify the currency to be used during Admin operations, such as Edit Order.

= 4.5.4.171109 =
* Tweak - Applied further optimisations to the installation process, to make it run in small steps and minimise the risk of timeouts.

= 4.5.3.171108 =
* Tweak - Improved compatibility of installation process with WP Engine and other managed WP hosts. The process now runs step by step, reducing the chance of timeouts and 502 errors.

= 4.5.2.171019 =
* Tweak - Improved settings page to make it clearer that the Open Exchange Rates service requires an API key.

= 4.5.1.171012 =
* Fix - Removed notice related to the conversion of shipping in WooCommerce 3.2.

= 4.5.1.170912 =
* Fix - Improved logic used to ensure that minicart is updated when the currency changes, to handle the new "hashed" cart fragment IDs.

= 4.5.0.170901 =
* Improved compatibility with WooCommerce 3.2:
	* Altered conversion of shipping costs and thresholds to support the new logic in WC 3.2.

= 4.4.21.170830 =
* Fixed conversion of shipping costs in WooCommerce 3.1.2.

= 4.4.20.170807 =
* Fixed display of coupon amounts in the WooCommerce > Coupons admin page.

= 4.4.19.170602 =
* Feature - New `wc_aelia_cs_get_product_base_currency` filter.

= 4.4.18.170517 =
* Improved compatibility with WooCommerce 3.0.x:
	* Removed legacy code that could trigger a warning.

= 4.4.17.170512 =
* Improved compatibility with WooCommerce 3.0.x:
	* Added workaround to issue caused by the new CRUD classes always returning a currency value, even when the order has none associated.

= 4.4.16.170424 =
* Improved compatibility with WooCommerce 3.0.x:
	* Fixed handling of coupons. Altered logic to use the new coupon hooks.
* Fixed issue of stale data displayed in the mini-cart. Added logic to refresh the mini-cart when the currency is selected via the URL.

= 4.4.15.170420 =
* Improved compatibility with WooCommerce 3.0.3:
	* Added logic to ensure that orders are created in the correct currency in the backend.
* Improved backward compatibility of requirement checking class. Added check to ensure that the parent constructor exists before calling it.

= 4.4.14.170415 =
* Improved performance of reports and dashboard.

= 4.4.13.170408 =
* Fixed bug in logic used to retrieve exchange rates. When the configured exchange rate provider could not be determined, the original logic tried to load an invalid class.
* Set default provider to Yahoo! Finance, to replace the unreliable WebServiceX.

= 4.4.12.170407 =
* Improved compatibility with WooCommerce 3.0.1:
	* Fixed bug caused by WooCommerce 3.0.1 returning dates as objects, instead of timestamps.

= 4.4.11.170405 =
* Improved compatibility with WooCommerce 3.0:
	* Fixed deprecation notice in Edit Order page.
* Fixed logic used to retrieve customer's country when the "force currency by country" option is active.

= 4.4.10.170316 =
* Added new filter `wc_aelia_currencyswitcher_product_base_currency`.
* Changed permission to access the Currency Switcher options to "manage_woocommerce".

= 4.4.9.170308 =
* Fixed minor warning on Product Edit pages.

= 4.4.8.170306 =
* Improved compatibility with WooCommerce 2.7:
	* Replaced call to `WC_Customer::get_country()` with `WC_Customer::get_billing_country()` in WC 2.7 and newer.
* Updated requirement checking class.
* Improved user experience. Added links and information to configure the Currency Switcher.
* Improved Admin UI. Added possibility to sort the currencies from the Currency Switcher Admin page.

= 4.4.8.170210 =
* Improved compatibility with WooCommerce 2.7 and 3rd party plugins:
	* Improved currency conversion logic to prevent affecting plugins that use `$product->set_price()` to override a product price.

= 4.4.7.170202 =
* Improved compatibility with WooCommerce 2.7:
	* Fixed infinite recursion caused by the premature loading of order properties in the new DataStore class.
	* Added caching of orders, for optimised performance.
* Removed obsolete code.
* Improved logic to determine if a product is on sale. The new logic can fix incompatibility issues with 3rd party plugins, such as Bundles.

= 4.4.6.170120 =
* Optimised performance of logic used for conversion of product prices.
* Removed integration with Dynamic Pricing plugin. The integration has been moved to a separate plugin.

= 4.4.5.170118 =
* Updated integration with BE Table Rates Shipping plugin.

= 4.4.2.170117 =
* Improved logger. Replaced basic WooCommerce logger with the more flexible Monolog logger provided by the AFC.

= 4.4.1.170108 =
* Improved compatibility with WooCommerce 2.7:
	* Refactored currency conversion logic to follow the new guidelines.
	* Replaced obsolete filters.
	* Added support for the new logic for the conversion of variable products.
