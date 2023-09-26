=== Multi-Inventory add-on for ATUM ===

Contributors: stockmanagementlabs, salvamb, japiera, agimeno82, dorquium, janberebel, danielberebel
Tags: multi-inventory
Requires at least: 5.0
Tested up to: 5.7.1
Requires PHP: 5.6
WC requires at least: 3.6.0
WC tested up to: 5.2.2
Stable tag: 1.5.6
License: ©2021 Stock Management Labs™

== Description ==

WooCommerce, as the most popular WordPress e-commerce solution, did not include an option for business selling from different warehouses or using several suppliers.
Up until now, shop owners have had a choice to either find a 3rd party solution or to have one built specifically for their site.
Neither solution is ideal unless the shop has a significant budget to cover the cost.

ATUM's Multi-Inventory premium add-on is trying to remove the above issues and brings advanced features for a fraction of the cost charged by 3rd party service. Now you, as a shop owner have the option to add as many inventory records per product as your business needs. What is even better, the system is trying to push the editing boundaries far beyond the usual standard.


== Changelog ==

---

`1.5.6`

*2021-04-30*

**Features**

* Add unassigned inventories to order items when order status is set to completed.
* Collect data when creating order notes and save metas.

**Changes**

* Refactoring.
* Changes to dark mode colours.
* Use the ATUM's font icon for child arrows everywhere.

**Fixes**

* CSS fix.
* Fixed inventory order items not being handled on WC orders on API requests.
* RTL fixes.
* Fixed updating MI locations from Stock Central.
* Ensure price 0 is allowed in Multi-Price.
* Fixed checkbox placement for MI rows in List Tables.
* Fixed conflict with AutomateWoo when returning an empty comment.
* Avoid removing increase/decrease buttons at Inventory Logs.
* Multiple fixes when using Selectable Inventories.

---

`1.5.5`

*2021-04-15*

**Features**

* Set the right inventories (according to the customer's address) when adding inventories automatically from the backend and when region restriction is enabled.

**Fixes**

* Fixed supplier filtering for MI in Stock Central.
* Fixed warning on MI supplier products hook.
* Fixed variation price not shown in frontend when the main inventory is out of stock and multi-price is enabled.
* Fixed syntax error on MI's ProductData extender.

---

`1.5.4`

*2021-04-08*

**Features**

* Added the "is_main" key to the inventory order items' extra_data.

**Changes**

* Refactoring.
* Validate API requests to product data endpoint when the product has MI enabled.
* Do not run some upgrade scripts when it's a fresh install.
* Updated dependencies + use WebPack 4.
* Reduced change stock hooks' priorities.
* Removed help text and moved the help page link to the MI icon.
* Sort list table products by MI field.

**Fixes**

* Fixed show inventories bbe date in cart.
* Fixed missing mi_inventories array in API response when creating a product with MI enabled.
* Added missing MI params to product data API endpoint.
* Check manage stock only at inventory level as product was excluding managed inventories.

---

`1.5.3`

*2021-03-16*

**Changes**

* Added filter when removing an inventory order item.

**Fixes**

* Fixed wrong status set in "chg_stock_order_complete" option.
* Fixed error when HTTP_REFERER is still not available.
* Ensure that an order item has the right type before processing it.
* Fixed wrong total stock calculated in MC from all cases.

---

`1.5.0`

*2021-03-12*

**Features**

* Added region to inventory creation modal.
* Add confirm alert when editing WC orders and changing status if it has MI line items without selected inventories.
* Added support for new MC column (available to produce).
* Allow editing an order item's qty when no inventory is assigned.
* Allow price 0 in inventories with multi-price.
* Added a security check to avoid changing the stock multiple times for the same order.
* Added the BOM tree to orders with MI-enabled products + linked BOMs but without any inventory set.

**Changes**

* Get WC order statuses that change the stock from ATUM Globals.
* Prevent changing stock when inserting/removing inventories in an order item.
* Allow deleting the last inventory depending on the order line reduced stock.
* Cancel inventory refund after deleting order refund.
* Handle the increase/decrease stock buttons actions through a JS hook.

**Fixes**

* Reset mi_ui property for each variation.
* Fixed inventories manually ordered by dates.
* Delete order item inventories when an ATUM order is deleted.
* Fixed wrong status name comparision.
* Fixed issue with PO creation through API requests that were adding ghost inventories.
* Recalculate order item totals after adding/updating a PO with MI order items through the API.
* Fixed gross profit when prices include taxes and multi-price is enabled.
* Fixed discounts total not being displayed when applied on order item inventories.
* Prevent sending the span tag in searches when the MI option 'createEmptyOrderItems' is active.
* Fixed subtotal cache causing wrong calculated discounts in order items.
* Removed the MI buttons for increasing/decreasing stock in Inventory Logs.
* Fixed inventory locations cannot be edited from List Tables.
* Avoid error when showing products at WC CLI.
* Fixed MI panel properties for new variations only binding the first inventory added.

---

`1.4.9`

*2021-02-19*

**Features**

* Allow editing MI data from List Tables.
* Allow changing stock only when transitioning WC orders to 'completed'.
* Display and edit the inventory locations from Stock Central.
* Show inventory locations in List Tables even when the term has no products assigned.
* Allow editing inventory regions from List Tables.
* Added hook action before echoing an inventory.
* Sort inventories under their parent at ListTables.
* Sort inventories by ID if products are being ordered by ID.
* Added extra atts filter to order item's MI panel.

**Changes**

* Allow until 8 decimal positions in ATUM order items inventories.
* Prevent MI products stock changes when managing order items from the backend and order status is distinct from 'on-hold'.
* Refactoring.
* Do not force adding inventories to any PO item until its status is changed to 'received'.
* Do not set to zero the default quantity value when adding a MI product to a PO.
* Updated MI order item tooltip message.
* Disable the order item fields when the first inventory gets added to a PO.
* Added antialiasing for all the ATUM font icons everywhere.
* Merged the inventory locations column with the products location column in Stock Central.
* Moved the regions column to the last position of inventory details.
* Show the MI rows with a green light backgound to easily identify them.
* Added a green background color to the multi-inventory details column group.
* Replaced switchery by a 100% CSS switcher.

**Fixes**

* Fixed order item inventories saving error.
* Fixed updating inventory data was changing unwanted props.
* Fixed incorrect UPSATE SQL statement.
* Fixed not saving main inventory metaboxes on variation products.
* Save the regions correctly from SC even when the region restriction is set to 'countries'.
* Fixed inventory order's reduced stock qty can't be set to null.
* Prevent possible incongruences when increasing stock.
* Added the wp-hooks dependency to ensure they are loaded by WordPress.
* Fixed cache saving for non-MI items was breaking the order items' subtotal calculation.
* Removed set_cache to avoid error calculating order item subtotal.

---

`1.4.8`

*2021-02-03*

**Features**

* Added the gross profit colum values for MI items.

**Changes**

* Adjusted hook names.

**Fixes**

* Do not show the gross profit on products with MI and multi-price enabled.

---

`1.4.7`

*2021-01-29*

**Features**

* Performance improvement: prevent reading all Shipping zones data when not needed.
* Added new hook after the MI UI buttons.
* Added MI's row actions to Stock Central and Manufacturing Central.
* Added ability to create new inventories from Stock Central and Manufacturing Central.

**Changes**

* Remove tooltip overflow behaviour because is not being used.
* Prevent editing the Main Inventory's stock in ListTable if the product has linked BOMs and Stock Control is enabled.
* Check is_on_sale separately for variations in variable products.
* Added class to inventories with stock negative in the BOM tree.
* Upgraded popovers and tooltips to Bootstrap 5.
* Added extra data to MI rows on List Tables.

**Fixes**

* Add inventory extra data if not set in db.
* Fixed no inventory added when ading an "out of stock" product to a WC order from the backend.
* Fixed MI UI not showing on some cases.
* Prevent ATUM order items deletion when recalculating orders if total = 0.

---

`1.4.6`

*2021-01-05*

**Features**

* Allow saving shipping class to the Main Inventory via model.
* Use the new JS hook when an order item gets removed.

**Changes**

* Unify how the products existence is checked.
* Refactoring.

**Fixes**

* Prevent notice when updating a PO but not its items from the API.
* Fixed subtotals in cart when using bundle products with multi-price.
* Fixed error when trying to use $this on the selectable inventories list view.
* Fixed all the jQuery deprecations until version 3.5.

---

`1.4.5`

*2020-12-16*

**Features**

* Do not load the ATUM Order items when not needed to improve performance.

**Changes**

* Use the new helper to get the current timestamp.
* Use the new AtumAdminNotices component when showing notices.
* Updated cloning inventories button's icon.

**Fixes**

* Fixed bundled products prices with discount on regular price with and without multi-price.


---

`1.4.4`

*2020-11-27*

**Changes**

* Updated SweetAlert2 dependency.
* Refactoring.
* Do not require the $region_restriction_mode variable on inventory-info template.

**Fixes**

* Fixed Expand/Collapse all inventories' buttons.
* CSS fixes.
* Fixed missing min quantity item for bundled items in cart price calculation.
* Check whether a param is present before adding hooks to the InventoryOrders endpoint.
* Ensure selectable inventories are appended only to wc variations.
* Fixed wrong shipping class applied when inventories with higer priority were out of stock.
* Fixed MI restrictions not being properly calculated when WPML is active.

---

`1.4.3`

*2020-11-13*

**Features**

* Added pre-update inventory action hook.
* Added new endpoint to allow batch for all the inventories (no matter its product).
* Added auto order item inventory creation during API requests.

**Changes**

* Set min node version to 14 and added jquery as webpack external.
* Refactoring.
* Added support for ES2017 to tsconfig.json.

**Fixes**

* Fixed methods migrated to Orders.
* Fixed bundled items prices having individually price with percent discount.

---

`1.4.2`

*2020-10-27*

**Features**

* Added getters for obtaining default_data and default_meta for inventories.
* Added a new /inventories endpoint to ATUM API to retrieve all the registered inventories.

**Changes**

* Refactoring.

**Fixes**

* Fixed type mismatch in visitor_location in some cases.
* Fixed mi_inventories for orders not being created through API requests.
* Fixed issue with autm_stock_status not being updated for products with MI that had no price.
* Fixed wrong logic operator.
* Fixed delete button not being shown on the first order item inventory on POs.
* Fixed BBE dates shown in cart for unused inventories.

---

`1.4.1`

*2020-10-08*

**Features**

* Send stock notifications linked to inventory instead of product.
* Added full compatibility for reserved stock to MI products.
* Added new custom filter for MI lines' subtotals.
* Added new filter to be able to save more extra data for order item inventories.

**Changes**

* Refactoring.
* Hide stock quantity from selectable inventories when WC stock format setting is "no_amount".
* Added WC changes into MI ReserveStock class.
* Hide prices from "out of stock" inventories at frontend.
* Keep search data for searching also in secondary inventories.

**Fixes**

* Fixed searching inventories by SKU or Supplier SKU at ATUM list tables.
* Fixed wrong text domains on some strings.
* Fixed popover's arrow CSS.
* Fixed datepicker not showing translated weekdays according to the users' locale.

---

`1.4.0`

*2020-09-23*

**Features**

* Added region restriction when changing shipping data from cart.
* Added inventory SKU and inventory supplier SKU in PO's PDF.
* Added bundled min and max qtys when calculating bundle price.
* Added inventories' data where creating a renewal order.
* Allow managing order inventory details from deleted inventories.
* Added Default value for existing inventories.
* Added filter to allow others to check the reserved stock.

**Changes**

* Preselect supplier inventory in PO with unique supplier.
* Order priority selecting inventory for unique supplier in PO.
* Show SKU and Supplier's SKU only on inventory details instead of order item details.
* Check if the product exists before refreshing its multi-price transient.
* Force saving the ATUM location cookie in root path.
* Load the new inventory template for order items only once.
* Refactoring.
* Handle unmanaged selectable inventories in front list and dropdown.
* Changed inventory ID replacement strategy for new order item inventories.
* Set the default quantity for new order item inventories to 1.
* Restore stock from expired inventory when removing the BBE date.
* Added CSS class to expired MI checkbox to avoid PL's behavior.
* Make sure the main inventory is always returned as a MainInventory object.

**Fixes**

* Fixed inventories endpoint's batches method.
* Fixed Geoprompt's UI styles.
* Fixed cache key being calculated wrongly.
* Fixed expired stock inventory meta.
* Fixed updating the main inventory meta through the inventories' API endpoint.
* Fixed the shipping cost calculate when adding inventories automatically.
* Allow backorders with multi-inventory.

---

`1.3.9`

*2020-08-27*

**Changes**

* Adjust product's status stock shown in product list for manging stock variable.
* Make nullable the datetime fields in the inventory's reserved stock table.
* Allow changing price HTML for bundled items.
* Hide product's shipping class field when MI is enabled.

**Fixes**

* Fixed bundle prices being wrong calculated for multi-priced products.
* Fixed bundled item prices being shown with the bundled discount if set.
* Fixed non-individually priced bundled items being included in the bundle product price calculation.
* Fixed bundled item's discount being applied to the regular price when calculating the bundle's price.
* Fixed wrong bundle price displayed in the backend when MI is added.
* Fixed changes in MI products not being updated in the bundle product.
* Fixed wrong item price being calculated for bundles when multi-price was active.
* Fixed wrong price being taken to calculate totals when inventory wasn't managing stock.
* Prevent applying discount twice in bundle products for multi-price products.

---

`1.3.8.1`

*2020-08-07*

**Changes**

* Alter the inventory reserved table to make nullable the datetime fields.

**Fixes**

* Fixed variable products with "manage stock" activated at product level showed incorrect stock status.
* Fixed Bundle product incorrect price showed when one or more of its bundled products had the Multi Price option active.

---

`1.3.8`

*2020-07-30*

**Features**

* Added new custom filter to "get_inventory_stock_on_hold" statuses.
* Added ReserveStock class to handle reserved stock in WC 4.3.
* Added class to order item inventory rows when multi price is enabled.

**Changes**

* Check whether the shipping cost exists before altering it.
* Refactoring.
* Prevent available stock values less than 0.
* Make the MI order items JS component globally available so can be used externally.

**Fixes**

* Fixed error when checking for selectable inventories of an item that is not a product.
* Fixed bundle price wrongly set when no multi-priced bundled items present.
* Fixed wrong quantity being saved to product's line item in cart when changing quantities multiple times.

---

`1.3.7.1`

*2020-07-10*

**Features**

* Added new feature to allow customers to select the inventories to use.
* Added 2 distinct UIs for selecting inventories (dropdown or list).
* Allow enabling the "selectable inventories" and its UI mode, globally or per product.
* Allow managing the selected inventories in product pages and/or from the cart.
* Added a new shipping class field to all the inventories (so extra shipping costs can be applied, according to the WC configuration, when an inventory is used).
* Added backorder values for MI products in Stock Central.

**Changes**

* Updated JS dependencies.
* Changed WC's product list stock status filtering from "chg_wc_product_meta_lookup" to "atum_product_data".

**Fixes**

* Fixed GeoPrompt loop when a privacy page is set in WC but the privacy link is left empty.

---

`1.3.6.1`

*2020-06-22*

**Fixes**

* Fixed version number.

---

`1.3.6`

*2020-06-19*

**Features**

* Performance improvement: prevent duplicated queries on get_inventory_sold_last_days.
* Added the inventory SKU to MI order items and hide the product's SKU.
* Allow filtering inventories by supplier + product type.
* Added full compatibility with multi-price and WC Bundles (also handling item discounts).
* Added compatibility for multi-prices on bundled items within orders.
* Add item inventories when importing a WC Order to an Inventory Log.
* Added "atum/atum_order/add_stock_change_note" filter to order stock change ajax calls.
* Show a list of SKUs for every inventory with stock on the products' pages.
* Added new action after saving order item inventories.

**Changes**

* Prevent accessing non existing  elements in the Prices Transient.
* Recalculate again the sales props when changing an order's status.
* Listen for inventory info fields' changes within variations.
* Ensure Main Inventories have the correct stock status when adding items from backend.
* Adjusted select2 components to follow the enhancedSelect component conventions.
* Prevent "headers already sent" errors when deleting the atum_location cookie.
* Refactoring

**Fixes**

* Fixed object to array conversion error when searching by supplier on List Tables.
* Fixed manual sorting mode not being saved on variations.
* Fixed wrong chosen price in WC Orders for multi-price inventories.
* Fixed transparent background on GeoPrompt popup.
* Fixed edit popover content not being shown when there were duplicated HTML IDs.
* Fixed error when refreshing the multi-prices transient when cancelling an order.
* Show the right subtotal when showing the order details for a bundled item with multi-price.
* Save the correct line totals when adding a new order item for a bundled item.
* Fixed MI not changing the stock status when disabling the out of stock threshold.
* Fixed add_action hook being used instead of add_filter.
* Fixed stock quantity for products with MI + BOMs and BOMs with MI when region restriction is enabled.
* When filtering by supplier in List Tables, hide the inventories not matching with it.
* Fixed PO always taking the first inventory's purchase price.
* Prevent variable products from being shown in Stock Central when filtering by supplier + non-variable product type.

---

`1.3.5`

*2020-05-29*

**Features**

* Added new custom hooks.
* Added batch tracking functionality to WC Orders and ATUM Orders.
* Allow tracking BOM MI order items' batch numbers.
* Improve "get_product_inventories" by reducing SQL queries.
* Performance improvements when using MI + Product Bundles together.
* Performance improvement: prevent "get_prop" execution in MultiPrice if not needed.
* Performance improvement: added multi-price transient.

**Changes**

* Include ATUM orders' actions when increasing/decreasing the order stock.
* Add by default an "in stock" inventory for WC orders and an "out of stock" inventory for POs.
* Changed compatible product types for "get_product_multi_inventory_status".
* Set the product as "on sale" when at least one of its inventories is on sale.
* Apply extra filters to inventories when filtering by supplier.
* Refactoring.
* Return main inventory props if no inventory is found.
* Prevent running the "atum/product_data/after_save_data" hook multiple times for the same products when updating inventory data on SC.
* Added several hooks to refresh the multi-price transient.
* Disable the variations buttons if there are still no changed variations.
* Allow HTML tags in region labels within order item inventories.

**Fixes**

* Fixed stock being increased when doing a quick or bulk edit from the products list.
* Fixed item line subtotal not being refreshed after setting the purchase price.
* Prevent duplicated queries in "get_zones_matching_package" function.
* Fixed stock for items with MI disabled but without order item inventories not being processed when switching order statuses.
* Fixed Main Inventory meta data not being read.
* Fixed SQL error when filtering by supplier.
* Fixed duplicated queries in Stock Central.
* Prevent all the variations to be saved always (no matter whether changes were made or not).
* Fixed meta backorders were'nt properly read in "Inventory::get_product_inventories".
* Fixed the MI products filter in List Tables.
* Fixed wrong array returned in "get_product_used_regions".

---

`1.3.4.1`

*2020-05-13*

**Fixes**

* Fixed bug when checking MI status for something that is not a product.

---

`1.3.4`

*2020-05-08*

**Features**

* Overall performance improvements.
* Reduced SQL queries complexity.
* Removed duplicated queries.

**Changes**

* Added default value to GeoPrompt exclusions.
* Added trigger after adding Inventory JS Event in ATUM's data panel.
* Escape slashes when checking GeoPrompt's exclusion patterns.
* Added special case for GeoPrompt's homepage exclusion.
* Hide WC's inventory fields in translated products with MI activated.
* Update product's calculated fields after saving meta boxes.
* Prevent accessing order items if order type is not supported.
* Added new custom filter for order item inventories.
* Added getter for the compatible child product types.
* Commented out the "delete_items" for the inventories endpoint (not used).
* Use the very first inventory (no matter its availability) when adding items to POs or ILs.
* The available inventory stock should be 0 when reaching the OOST.
* Refactoring.

**Fixes**

* Fixed GeoPrompt's select2 dropdown with transparent background.
* Fixed "manage_stock" not being updated for inventories through ATUM API.
* Fixed WPML's current saved product preventing other products to return the correct stock.
* Fixed wrong name "date on sale to/from" on accessed properties.
* Fixed wrong PO's subtotal/total calculations when editing MI items' quantities.
* Fixed wrong setting existence checking in JS component.
* Fixed no refunds being sent to payment gateways when a product had MI enabled.
* Fixed MI's "has_compunded" class not included in Manufacturing Central.
* Fixed invalid number of params registered for an action.
* Fixed wrong discount total being added to POs' PDF.
* Fixed ATUM orders breaking when an item's product doesn't exist anymore.
* Fixed multi-price products adding extra value when included in a bundle.
* Fixed MI filters preventing variations to be created.
* Fixed MI product settings not being displayed correctly for variations.

---

`1.3.3`

*2020-04-03*

**Changes**

* Use the ATUM's UIPopovers component.
* Removed NiceSelect dependency.
* Removed accounting.js dependency and use Utils component helpers instead.
* Change FormatMoney calls parameters.
* Added a custom hook after adding a MI order item.
* Added editorconfig.
* Refactory.

**Fixes**

* Fixed wrong product's stock discounted for non original langs in WPML.
* Fixed line item data can't be changed in MI lines if no stock is changed.
* Fixed wrong link to the ATUM Locations' edit page.
* Fixed PO inventory's purchase price being included with taxes when the option "prices include taxes" is set.
* Fixed phantom discounts displaying in WC Orders' MI items.
* Avoid unexpected error when refunding orders with MI items.
* Fixed Inventories not changing their stock status when enabling/disabling the ATUM's OOT option
* Text typo fix.
* Fixed wrong select width on the ATUM's MI panel when using Firefox browser.
* CSS fixes.

---

`1.3.2`

*2020-03-06*

**Changes**

* Added Inventory props to PHPDoc so can be accessed easily.

**Fixes**

* Fixed negative order subtotals in backordered MI products with multi-price.
* Fixed PP removal from PO items with MI enabled.
* Refactory.

---

`1.3.1`

*2020-02-25*

**Features**

* Added a new textarea to settings to be able to list the excluded pages for the GeoPrompt.
* Added new filter to List Tables to be able to show MI/non-MI products only.
* Hide the MI status icon when filtering by non-mi products.
* New feature to show expiry dates in cart (when enabled).
* New "Expiry Days" field to set the inventories as "out of stock" the specified number of days before theis BBE dates.

**Fixes**

* Prevent products from disappearing from the loop on some cases after activating MI.
* Fixed the Manage Stock tool not working for inventories.
* Fixed insert inventory's order items not changing order items' data.
* Fixed tooltip for not deducted stock that was being shown even if the stock has been already deducted.
* Allow the supplier to be changed for Main Inventories.
* Refactory.

---

`1.3.0`

*2020-02-07*

**Features**

* Added full compatibility with ATUM Product Levels.
* Added inventories to Manufacturing Central.
* Added the inventories to the hierarchy BOM tree.
* Delete the inventories when the product type is changed to a non-compatible type.
* Do not add inventories to non-compatible product types on Stock Central.
* Duplicate the MI data when duplicating a product.
* Remove all the inventories when deleting a product.
* Hide the PL fields from non-main inventories in BOM variations.
* Split updating order inventories in two steps to make PL working.
* Added message to settings to suggest enabling the MaxMind service (WC 3.9+).
* Enable the geolocation API fallback when needed (WC 3.9+).
* New ability to assign a default region to all the inventories that have no region assigned.

**Changes**

* Updated dependency versions.
* Added new hook to be able to add extra icons to order items.
* Do not add the collapse class when clicking order items with MI.
* Changed the logic for PL icon tooltip message.
* Removed BOM fields from non-main inventories.
* Clear the LOT field when adding a new inventory.
* Trigger an ATUM action after saving a product from ListTables.
* Do not save a Main Inventory automatically if there is one coming on a form submission.
* Added Inventory stock changes when changing order line items from the backend.
* Added the missing stock indicator icons to PDF reports.
* Changed managing order inventory items behaviour to adapt to WC.
* Disabled the stock status rebuilding for MI products.
* Get rid of the "ATUM_PREFIX" constant from db table names to avoid issues.
* Added the premium support link to the plugin details on the plugins page.

**Fixes**

* Fixed error when trying to add an order item to a new order (not yet saved).
* Fixed ATUM MI icon not toggling the order item inventories.
* Fixed variation's MI fields visibility issue.
* Fixed caching issue on Stock Central.
* Fixed order item inventories not being deleted for ATUM orders.
* Check if the cart exists before getting coupons.
* Fixed removing an order item inventory by setting qty to 0 was causing the stock to decrease.
* Fixed the ATUM data removal when a product is deleted.
* Fixed featherLight dependency version issue.
* Avoid the main inventory from being created duplicated for all products (with and without MI active).
* Fixed Inventory::get_product_inventories was setting wrong inventories in cache.
* Prevent compatible parent products from showing the MI UI.
* Fixed stock indicator icon colors messed when MI is enabled for some listed products.
* Fixed MI events to being binded when a new variable product is being created.
* Restore the WC fields visibility when switching the Virtual or Downloadable checkboxes.
* Fixed undefined variable notice in User Destination Form.
* Avoid eventual errors when refunding orders.
* CSS fixes.
* Refactory.

---

`1.2.5.2`

*2019-12-13*

**Fixes**

* Fixed bulk editing order status was causing wrong stock levels changes.
* Fixed Geo prompt's required fields not being saved on ATUM Settings.
* Prevent no longer existing zones to be shown in MI settings' tools.
* CSS fixes.

---

`1.2.5.1`

*2019-12-05*

**Changes**

* Exclude write-off inventories from the Current Stock Value widget.
* If the out of stock items are being hidden, exclude the inventory prices from the product price range.
* Redo all the multi-price discounts' calculations.
* Prevent adding a Main Inventory to products without MI.

**Fixes**

* Fixed countries list on region restriction settings.
* Fixed order totals were not calculated correctly for multi-price products.
* Fixed total discount cart was not applied correctly for inventories with multi-price.
* Fixed refunds were not applied correctly for inventories with multi-price.
* Prevent the applied discounts to be greater than the inventory price when multi-price is enabled.
* Fixed parent color that was not propagated to all MI rows in Stock Central.
* Refactory.

---

`1.2.5`

*2019-11-14*

**Features**

* Added mi_inventories array to the Products' API endpoint.
* Added filtering to Products' API endpoint using MI fields.

**Changes**

* Exclude the mi_inventories REST field from the non-compatible or non-mi-enabled products.
* Allow setting no default shipping zone nor country region.
* CSS changes for accessibility (following WordPress 5.3 new styling).

**Fixes**

* Fixed returned shipping zone array even if an ids array wanted for default shipping zone.
* Fixed inventories that were saved in the next sibling variation.
* Fixed wrong arguments' order in the MI's Products API endpoint extender.
* Fixed issue with cache on API requests.
* Fixed ATUM locations not being hidden when MI wass active on simple products.
* Fixed term_id that was being saved instead of term_taxonomy_id within the inventory locations' table.

---

`1.2.4`

*2019-10-31*

**Features**

* Added Multi Inventory extension for the new ATUM REST API.
* Added Inventory order items to the WC Orders, Purchase Orders and Inventory Logs endpoints.
* Added Multi Inventory data to the Products and Variation Products endpoints.
* Added new endpoint for Inventories.

**Changes**

* Changed "selling_priority" meta name to "inventory_sorting_mode" to avoid confusion with the PL's "selling_priority".

**Fixes**

* Fixed product loop's wrong query if the WC option to hide the products when run out of stock is set.
* Prevent changing the query when is_admin.
* Fixed coupon discounts not applied properly when Multi-Price is active.
* Prevent applying twice the price excluding taxes.
* Save the correct MI line item's total when discounts are applied.
* Fixed wrong subtotal in orders' detail page for discounted multi-price products.
* Fixed wrong postmeta table spoecified in "add_mi_restrictions" query.
* Fixed wrong variation's index numbering set if some WC messages are present.
* Fixed wrong text domains.
* Fixed wrong prices and order subtotals shown for products with Multi-Price.
* Fixed wrong parent's stock status when MI wasn't activated in child products.
* Fixed wrong tax was applied to non-MI products in products loop.

---

`1.2.3.1`

*2019-10-11*

**Changes**

* Allow to set inventory stock counters as float.
* Changed inventory's table counter fields type.

**Fixes**

* Fixed Inbound Stock in Stock Central not updated when changing MI lines within POs.
* Check product object to avoid issues.
* Fixed inventory's stock on hold not properly calculated.
* Fixed wrong stock counters in Stock Central for MI products.
* Re-bind the MI UI settings when a new variable product is being created.
* Refactory.

---

`1.2.3`

*2019-09-20*

**Features**

* Allow searching within Multi-Inventory columns in Stock Central.
* Allow using the DateTimePicker's today button when the maxDate is set to "moment+1".

**Changes**

* Adapted gulpfile to work with Gulp 4.
* Moved the inventory expiration checker to a scheduled CRON.

**Fixes**

* Added missing sale price validation to Inventories.
* Do not clear the BBE date when it does not pass the DateTimePicker validation.
* Rebuild the Inventories' "Out of Stock Threshold" when changing the option from ATUM settings.
* Fixed wrong price HTML returned for non-multi-price products.
* Show the right stock indicator for products with inventories in Stock Central.
* GeoPrompt CSS fixes.

---

`1.2.2.2`

*2019-09-05*

**Changes**

* Updated JS dependencies.
* Updated gulpfile.
* Added a new hook to alert other plugins that Multi-Inventory has just activated.

**Fixes**

* Fixed lost color variable for Select2 components.
* Fixed wrong cache value set for the main inventory when Multi-Inventory is enabled globally.
* Do not check for the visitor location if the request is from and API call.

---

`1.2.2.1`

*2019-08-16*

**Fixes**

* Fixed Discount total amount not shown in cart when coupon has "fixed cart" type.
* Fixed Sale price scheduled dates not saved when price per inventory not active.
* Fixed Stock status not shown properly when Country restriction mode was activated.
* CSS fixes.
* Refactory.

---

`1.2.2`

*2019-07-31*

**Features**

* Adapted to the new ATUM colors feature.

**Changes**

* Added support for unmanaged inventories in WC orders.
* Added support for unmanaged inventories in ATUM orders.
* Added strict validation to inventory meta sanitization methods.
* Updated npm packages.
* Added compatibility for new WPML version.

**Fixes**

* Fixed language file not being loaded.
* Fixed Inventory's sale dates not being saved correctly.
* Fixed conflicts with ATUM's datepicker and 3rd party plugins.
* Allow saving correct values in de main inventory.
* Fixed Stock Central stock totals sum for backordered inventories when the "use next" option is set.
* Fixed checkout always setting the Main inventory stock to NULL.
* Prevent returning managed MI product's stock when decreasing stock.
* Allow setting the inventory's stock to null.
* Fixed tsconfig.json to support TypeScript 3.5.3.
* Fixed wrong Stock Central totals for Inventories.
* Prevent returning empty prices when Multi-Price is enabled.
* Added only stockable (with price) inventories to product getters.
* Added unstockable inventories (don't exist if not regular price is set).
* CSS fixes.
* Refactory.

---

`1.2.1.6`

*2019-06-28*

**Fixes**

* Prevent not an array warning when processing front-end orders.
* Remove inventories when a product is deleted (refactorized).

---

`1.2.1.5`

*2019-06-21*

**Fixes**

* Fixed sale prices dates' behaviour in variation products.
* CSS fixes.

---

`1.2.1.4`

*2019-06-14*

**Fixes**

* Fixed wrong calculations in orders when using non-standard decimal separators.

---

`1.2.1.3`

*2019-06-13*

**Changes**

* Changed stock on hold formula.

**Fixes**

* Fixed Inventory Logs showing wrong total when taxes were applied.
* Fixed date format in Inventory model.
* Prevent some payment gateWays to re-add inventory items to orders that were already processed.
* Fixed error on some cases when the "show out of stock" option is enabled.
* Fixed inventory data being displayed as unsaved when no changes were made.
* Fixed location/region removals and changes.
* Fixed cache conflict when chaging the shipping address in the checkout.
* Refactory.

---

`1.2.1.2`

*2019-06-03*

**Fixes**

* Fixed prices being saved incorrectly when not using dots as decimal points.
* Refactory.

---

`1.2.1.1`

*2019-05-24*

**Fixes**

* Prevent saving and showing Multi-Inventory's data within WPML translations.
* Refactory.

---

`1.2.1`

*2019-05-18*

**Changes**

* Added exclude path to TypeScript config.
* Check product compatibility before applying the multi-price logic.
* Hide Multi-Inventory fields and data for incompatible product types.

**Fixes**

* Fixed error when duplicating a product.
* Avoid conflicts with jQuery UI's datepicker.
* Rebind the DateTimePickers when cloning an inventory.
* Fixed DateTimePicker options object.
* Fixed prices not able to set empty values for the main inventories on Stock Central.
* Fixed inventories sorting not working on variations.
* Fixed issue when saving a WPML translation that was removing the inventories data.
* Fixed casting error on WPML integration.
* Fixed cart item total for Product Bundles when Multi Price is enabled.
* Fixed wrong stock status on bundled items with Multi-Inventory enabled.
* Fixed Supplier fields that were displayed in all producs when Multi-Inventory plugin was activated.
* Fixed wrong discounts being displayed for bundled items within orders.
* Fixed order items for bundled products.
* Fixed PHP notice when adding a Composite product to the cart.
* CSS fixes.
* Refactory.

---

`1.2.0.1`

*2019-05-08*

**Fixes**

* Fixed SQL syntax error.
* Refactory.
* CSS recompilation.

---

`1.2.0`

*2019-04-18*

**Features**

* Performance improvement: reduced number of db queries performed in Stock Central to the half.
* Performance improvement: added new key indexes to Multi-Inventory tables.
* Added all the calculated MI columns to Stock Central.

**Changes**

* Moved the Multi-Inventory tables creation to the Upgrade class.
* Moved the new MI columns to the inventories table.
* Added the inventory order items when the orders are created instead of when the stock is reduced.

**Fixes**

* Added the correct name when cloning an inventory, and open first one by default.
* Allow to calculate the proper stock when executing a non-ATUM Ajax action.
* SQL bug fix.
* Added all the calculated MI columns to Stock Central.
* Fixed blank cells showing on Sales Last Days column.
* Fixed decimal numbers issue when adding inventories to an order.
* Fixed wrong stock saved in products when WPML was active.
* WPML fix: prevent re-adding actions if they weren't added before.
* Fixed refund message not shown.
* Refactory.

---

`1.1.3`

*2019-04-06*

**Features**

* Completed JS Refactory to TypeScript.

**Changes**

* Removed woocommerce_admin dependency.
* Show Set Purchase Price only in Purcahse Orders.

**Fixes**

* Fixed Inventory Log notes were no reflecting stock inventory changes.
* Fixed duplicate status when the status changes in MI's product panel.
* Show correct message when writte off is marked/unmarked.
* Fixed inventory cloning.
* Change to correct status when clicked remove icon (write off) from an inventory.
* Fixed selling priority functionality and hide out stock threshold field when manage stock is enabled.
* Fixed min/max date in BBE date field.
* Fixed tooltip hiding in Inventory Logs.

---

`1.1.2`

*2019-03-29*

**Features**

* JS Refactory to TypeScript (work in progress).

**Fixes**

* Fixed Inventory Logs to take correct price when a product has multi price enabled.
* Fixed WC orders to show correct price when a product hass multi price enabled.
* Fixed bookable product prices shown if Multi-Inventory is active.
* Fixed drag control in inventory list inserted twice.
* Fixed compounded stock for variable products in Stock Central.
* Fixed Stock Central's checkboxes selection behaviour.
* Fixed issue in cart total price when Inventory Iteration is "Show out of stock".
* Fixed issue getting inventories with multi-price.
* Refactory.


---

`1.1.1.1`

*2019-03-13*

**Features**

* Allow to set distinct purchase prices for distinct inventories on Purchase Orders if multi-price is enabled.

**Changes**

* Delete all the Multi-inventory data when the option in ATUM settings is enabled.
* Purchase Order takes the first inventory's purchase price if multi-price is enabled.

**Fixes**

* Added subscription price when MI is enabled.
* Fixed stock not being dicounted properly when WPML is active.
* Fixed some hidden select2 when they shouldn't.
* Fixed missing notice argument in orders.
* Refactory.

---

`1.1.1`

*2019-03-08*

**Features**

* Performance improvements: reduced the number of db queries using cache.

**Fixes**

* Avoid CSS conflicts with other plugins using Select2.
* Avoid problems with file_get_contents getting the stylesheet from the file system.

---

`1.1.0.1`

*2019-03-01*

**Changes**

* Cache refactoring.
* Added compatibility to order refunds when using Product Levels with BOM Stock Control enabled.

**Fixes**

* Fixed adding regular price to subscription products.

---

`1.1.0`

*2019-02-22*

**Features**

* Added compatibility between Multi-Inventory and WC Product Bundles.
* Add MI UI when a product bundle is added to a WC order.
* Improved performance using cache.
* Added WC Subscription products compatibility.

**Changes**

* Updated MI section title style for variations.

**Fixes**

* Fixed get stock value returning availabe stock.
* Fixed Geo Prompt's infinite loop for logged-in users with no address fields set.
* Fixed using "wc_get_low_stock_amount" in WooCommerce versions lower than 3.5.
* Fixed cache not being removed when saving metaboxes.
* Adapted JS to the new ATUM model.
* CSS fixes.
* Refactory.

---

`1.0.7.6`

*2019-01-31*

**Fixes**

* Fixed: Frontend product stock status doesn't change when it runs out of stock and the first inventory is the Main inventory.

---

`1.0.7.5`

*2019-01-30*

**Fixes**

* Fixed "Allow backorders" field not saved properly in Inventories.
* Fixed "can be backordered" message not shown in the frontend when it should.
* Fixed max quantity a product can be bought not set properly in the frontend.

---

`1.0.7.4`

*2019-01-18*

**Changes**

* Moved bootstrap datetimepicker to ATUM.

**Fixes**

* Fixed Stock Central totalizers for products with MI enabled.
* Prevent "get_order_item_subtotal" to access items from other order types.
* Fixed bug when calculating quantities in Orders.
* Update the db for inventory order items set before this fix.
* Prevent Upgrade from running several times.
* Refactory.
* CSS fixes.


---

`1.0.7.3`

*2018-12-20*

**Fixes**

* Fixed undefined variable error.
* Fixed GeoPrompt CSS.
* Fixed inventories table creation SQL.
* Minor CSS fixes.

---

`1.0.7.2`

*2018-12-17*

**Changes**

* Using autoprefixer when compiling SCSS to CSS.

**Fixes**

* Text typo change.
* CSS fixes.

---

`1.0.7.1`

*2018-12-14*

**Changes**

* Updated order type for Invenory Log items.

**Fixes**

* Fixed stock status not updating when changing the Out of Stock Threshold.
* Fixed minimum versions checks.
* Fixed some wrong text domains.
* Fixed icons.
* CSS fixes.

---

`1.0.7`

*2018-12-11*

**Features**

* Performance improvements using cache.
* Added the max attribute to MI products' quantity input.
* Moved MI meta keys to table columns in db.
* Use Out of stock threshold as max units available for each inventory.
* Added stock status column to MI management popup in orders.
* Added unmanaged icons to order item inventories.

**Changes**

* Added ATUM 1.5.0 compatibility.
* Replaced image icons to ATUM icons.
* Remove region from MI management popup if not needed.
* Hide "add inventory" button when no more inventories are available to add.
* Set to 1 the minimum quantity by default when adding a product to an order when using decimals for stock.

**Fixes**

* Fixed Regions not being saved  in the right format.
* Fixed Write Off/ Un-write Off for inventories.
* Fixed PO not changing the MI stock when the status changes.
* Fixed out of stock threshold for inventories.
* Fixed product showing as "0 in stock" in front-end.
* Fixed duplicated "set purchase price" button in Purchase Orders.
* Fixed dates that cannot be removed after being set.
* Fixed stock quantities at front-end when some of the product inventories are not managing the stock.


---

`1.0.6`

*2018-11-08*

**Features**

* Added custom decimals to quantity fields.
* Prevent removing last assigned inventory to any to order line item.
* Check minimum required versions for PHP, WC and ATUM before loading.

**Changes**

* Hide stock quantity from inventory header for inventories with the "Manage Stock" disabled.
* Add the first available inventory item when adding Multi-Inventory products to orders.

**Fixes**

* Fixed forum URLs.
* Fixed order line totals not being calculated properly.
* Fixed: changing stock from ATUM orders could cause deleting stock in Main Inventory.
* Fixed inventory data not being saved from Stock Central.
* Fixed: Multi-Inventory panel in ATUM order items not being deleted when deleting the associated item.
* Fixed Inventory::get_metadata to return the full array of meta when no meta_key is passed.
* Refactory.

---

`1.0.5`

*2018-10-31*

**Fixes**

* Fixed Inventory manage stock field was not saved properly in not main inventories.

---

`1.0.4`

*2018-10-31*

**Features**

* Use cache to improve performance.
* Added WPML compatibility.

**Fixes**

* Fixed limit usage for multi-price item discounts.
* Refactory: code style.

---

`1.0.3`

*2018-10-30*

**Features**

* Added multi-level inheritable products multi price support when showing prices in the frontend.

**Fixes**

* Fixed inheritable products will show price 0 when multi price is disabled.
* Fixed discounts in multi price items.
* Fixed multi price products don't include/exclude the taxes when showing the price in the frontend.
* Fixed Multi price out of stock inventories price not included  when showing the price in the frontend.

---

`1.0.2`

*2018-10-29*

**Features**

* Added a privacy page field to Geo Prompt settings when using a WC version older than 3.4.0.
* Added a privacy page field to User Destination widget settings when using a WC version older than 3.4.0.

**Changes**

* Do not show the form confirmation checkbox if the Privacy Text field is empty.

**Fixes**

* Fixed compatibility with WooCommerce 3.0.0.

---

`1.0.1`

*2018-10-26*

**Features**

* The first public release of Multi-Inventory add-on. Check the add-on page for more info: https://www.stockmanagementlabs.com/addons/multi-inventory/