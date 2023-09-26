<?php
/**
 * Helper functions
 *
 * @package        AtumMultiInventory
 * @subpackage     Inc
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2021 Stock Management Labs™
 *
 * @since          1.0.0
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Models\Log;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Supplier;
use AtumMultiInventory\Models\Inventory;
use AtumMultiInventory\Models\MainInventory;
use AtumMultiInventory\MultiInventory;


final class Helpers {

	/**
	 * Return if a product has multi inventory capability
	 *
	 * @since 1.0.1
	 *
	 * @param int|\WC_Product $product The product ID or product object.
	 * @param bool            $use_cache
	 * @param bool            $strict Whether to get only the product types able to have multi-inventories or not.
	 *
	 * @return bool
	 */
	public static function is_product_multi_inventory_compatible( $product, $use_cache = TRUE, $strict = FALSE ) {

		$enabled    = FALSE;
		$cache_key  = NULL;
		$product_id = $product instanceof \WC_Product ? $product->get_id() : $product;

		if ( $use_cache ) {
			// Use cache to improve performance.
			$cache_key = AtumCache::get_cache_key( 'product_multi_inventory_enabled', [ $product_id, $strict ] );
			$enabled   = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN );
		}

		if ( FALSE === $enabled ) {

			// Prevent reload the product if got it.
			if ( ! $product instanceof \WC_Product ) {
				$product = wc_get_product( $product_id );
			}

			if ( $product instanceof \WC_Product ) {

				$enabled = $strict ?
					MultiInventory::can_have_assigned_mi_product_type( $product->get_type() ) :
					MultiInventory::is_mi_compatible_product_type( $product->get_type() );
			}
			else {
				$enabled = FALSE;
			}

			if ( $cache_key ) {
				AtumCache::set_cache( $cache_key, wc_bool_to_string( $enabled ), ATUM_MULTINV_TEXT_DOMAIN );
			}

		}
		else {
			$enabled = wc_string_to_bool( $enabled );
		}

		return $enabled;

	}

	/**
	 * Get the multi inventory status for the specified product
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Product $product      The product ID or product object.
	 * @param bool            $allow_global Optional. If FALSE, only can return 'yes' or 'no'. If TRUE, it could return 'global'.
	 *
	 * @return string
	 */
	public static function get_product_multi_inventory_status( $product, $allow_global = FALSE ) {

		$product   = AtumHelpers::get_atum_product( $product );
		$has_cache = FALSE;

		// Use cache to improve performance.
		if ( $product instanceof \WC_Product ) {
			$cache_key = AtumCache::get_cache_key( 'product_multi_inventory_status', [ $product->get_id(), $allow_global ] );
			$status    = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );
		}

		if ( ! $has_cache ) {

			// Disable for all the non-compatible methods.
			if ( ! $product instanceof \WC_Product || ( $product instanceof \WC_Product && ! MultiInventory::can_have_assigned_mi_product_type( $product->get_type() ) ) ) {
				$status = 'no';
			}
			else {
				$status = AtumHelpers::get_product_prop( $product, 'multi_inventory', 'no', 'mi', $allow_global );
			}

			if ( isset( $cache_key ) ) {
				AtumCache::set_cache( $cache_key, $status, ATUM_MULTINV_TEXT_DOMAIN );
			}

		}

		return $status;
	}

	/**
	 * Get the inventory iteration for the specified product
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Product $product      The product ID or product object.
	 * @param bool            $allow_global Optional. If FALSE, only can return 'use_next' or 'out_of_stock'. If TRUE, it could return 'global'.
	 *
	 * @return string
	 */
	public static function get_product_inventory_iteration( $product, $allow_global = FALSE ) {

		$product = AtumHelpers::get_atum_product( $product );

		return AtumHelpers::get_product_prop( $product, 'inventory_iteration', 'use_next', 'mi', $allow_global );

	}

	/**
	 * Get the inventory sorting mode for the specified product
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Product $product      The product ID or product object.
	 * @param bool            $allow_global Optional. If FALSE, only can return 'fifo', 'lifo', 'bbe' or 'manual'. If TRUE, it could return 'global'.
	 *
	 * @return string
	 */
	public static function get_product_inventory_sorting_mode( $product, $allow_global = FALSE ) {

		$product = AtumHelpers::get_atum_product( $product );

		return AtumHelpers::get_product_prop( $product, 'inventory_sorting_mode', 'fifo', 'mi', $allow_global );

	}

	/**
	 * Get the expirable inventories option status for the specified product
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Product $product      The product ID or product object.
	 * @param bool            $allow_global Optional. If FALSE, only can return 'yes' or 'no'. If TRUE, it could return 'global'.
	 *
	 * @return string
	 */
	public static function get_product_expirable_inventories( $product, $allow_global = FALSE ) {

		$product = AtumHelpers::get_atum_product( $product );

		return AtumHelpers::get_product_prop( $product, 'expirable_inventories', 'no', 'mi', $allow_global );

	}

	/**
	 * Get the price per inventory option status for the specified product
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Product $product      The product ID or product object.
	 * @param bool            $allow_global Optional. If FALSE, only can return 'yes' or 'no'. If TRUE, it could return 'global'.
	 *
	 * @return string
	 */
	public static function get_product_price_per_inventory( $product, $allow_global = FALSE ) {

		$product = AtumHelpers::get_atum_product( $product );

		return AtumHelpers::get_product_prop( $product, 'price_per_inventory', 'no', 'mi', $allow_global );

	}

	/**
	 * Get the selectable inventories option status for the specified product
	 *
	 * @since 1.3.7
	 *
	 * @param int|\WC_Product $product      The product ID or product object.
	 * @param bool            $allow_global Optional. If FALSE, only can return 'yes' or 'no'. If TRUE, it could return 'global'.
	 *
	 * @return string
	 */
	public static function get_product_selectable_inventories( $product, $allow_global = FALSE ) {

		$product = AtumHelpers::get_atum_product( $product );

		return AtumHelpers::get_product_prop( $product, 'selectable_inventories', 'no', 'mi', $allow_global );

	}

	/**
	 * Get the selectable inventories mode option for the specified product
	 *
	 * @since 1.3.7
	 *
	 * @param int|\WC_Product $product      The product ID or product object.
	 * @param bool            $allow_global Optional. If FALSE, only can return 'yes' or 'no'. If TRUE, it could return 'global'.
	 *
	 * @return string
	 */
	public static function get_product_selectable_inventories_mode( $product, $allow_global = FALSE ) {

		$product = AtumHelpers::get_atum_product( $product );

		return AtumHelpers::get_product_prop( $product, 'selectable_inventories_mode', 'dropdown', 'mi', $allow_global );

	}

	/**
	 * Get an inventory object. Uses cache for better performance
	 *
	 * @since 1.0.7
	 *
	 * @param int  $inventory_id    The inventory ID to instantiate.
	 * @param int  $product_id      Optional. The product ID that the inventory belongs to.
	 * @param bool $main            Optional. Whether to return a MainInventory object.
	 *
	 * @return Inventory|MainInventory
	 */
	public static function get_inventory( $inventory_id, $product_id = 0, $main = FALSE ) {

		$key_main  = $main ? '_main' : '';
		$cache_key = AtumCache::get_cache_key( "get_inventory$key_main", [ $inventory_id, $product_id ] );
		$inventory = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache && $inventory instanceof Inventory ) {
			return $inventory;
		}

		/* @noinspection PhpUnhandledExceptionInspection */
		$inventory = $main ? new MainInventory( $inventory_id, $product_id ) : new Inventory( $inventory_id );

		// Make sure that the main inventory is always returned as a MainInventory object.
		if ( $inventory->is_main() && ! $inventory instanceof MainInventory ) {
			return self::get_inventory( $inventory_id, $inventory->product_id, TRUE );
		}

		AtumCache::set_cache( $cache_key, $inventory, ATUM_MULTINV_TEXT_DOMAIN );

		return $inventory;

	}

	/**
	 * Get the inventories linked to a specific product sorted by sorting mode
	 *
	 * @since 1.0.0
	 *
	 * @param int         $product_id         The product ID.
	 * @param bool        $not_write_off      Optional. Whether to return the inventories in "write-off" status too.
	 * @param bool        $skip_cache         Optional. Whether to skip the cache. It's used to calculate inventories for distinct zones within the same process.
	 * @param false|array $inventory_orderby  Optional. Order the inventories by a field.
	 *
	 * @return Inventory[]
	 *
	 * @throws \Exception
	 */
	public static function get_product_inventories_sorted( $product_id, $not_write_off = TRUE, $skip_cache = FALSE, $inventory_orderby = FALSE ) {

		// As this function could be called multiple times, let's cache it.
		$cache_key   = AtumCache::get_cache_key( 'product_inventories_sorted', $product_id );
		$has_cache   = FALSE;
		$inventories = [];

		if ( ! $skip_cache ) {
			$inventories = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );
		}

		if ( ! $has_cache ) {

			// Get all the inventories including Main.
			$inventories = Inventory::get_product_inventories( $product_id, '', FALSE, $not_write_off );

			if ( empty( $inventories ) ) {

				$inventories = [];
			}
			else {

				$inventory_sorting_mode = FALSE === $inventory_orderby ? self::get_product_inventory_sorting_mode( $product_id ) : 'field';

				// The inventories should have the priority col already sorted but in case was not saved properly, re-sort them again.
				switch ( $inventory_sorting_mode ) {

					// Last added sells first.
					case 'lifo':
						usort( $inventories, function ( $a, $b ) {

							$a_date = $a->inventory_date ? $a->inventory_date->getOffsetTimestamp() : '';
							$b_date = $b->inventory_date ? $b->inventory_date->getOffsetTimestamp() : '';

							if ( $a_date === $b_date ) {
								return 0;
							}

							if ( ! $a_date ) {
								return - 1;
							}

							if ( ! $b_date ) {
								return 1;
							}

							return $b_date - $a_date;

						} );

						break;

					// The shortest lifespan (nearest BBE date) sells first.
					case 'bbe':
						usort( $inventories, function ( $a, $b ) {

							$a_date = $a->bbe_date ? $a->bbe_date->getOffsetTimestamp() : '';
							$b_date = $b->bbe_date ? $b->bbe_date->getOffsetTimestamp() : '';

							if ( $a_date === $b_date ) {
								return 0;
							}

							if ( ! $a_date ) {
								return 1;
							}

							if ( ! $b_date ) {
								return - 1;
							}

							return $a_date - $b_date;

						} );

						break;

					// Set manually by user.
					case 'manual':
						usort( $inventories, function ( $a, $b ) {
							return $a->priority - $b->priority;
						} );

						break;

					// Order by field.
					case 'field':
						usort( $inventories, function ( $a, $b ) use ( $inventory_orderby ) {

							$field = $inventory_orderby['field'];
							$order = $inventory_orderby['order'];

							switch ( $field ) {
								case 'title':
									$field = 'name';
									break;
								case '_stock':
									$field = 'stock_quantity';
									break;
								case 'ID':
									$field = strtolower( $field );
									break;
								case '_selling_priority':
								case '_available_to_purchase':
									// No order.
									return 0;
								default:
									if ( '_' === substr( $field, 0, 1 ) ) {
										$field = substr( $field, 1 );
									}
									if ( 'mi_' === substr( $field, 0, 3 ) ) {
										$field = substr( $field, 3 );
									}
									break;
							}

							if ( $a->{$field} instanceof \DateTime || $b->{$field} instanceof \DateTime ) {

								$a_date = $a->{$field} ? $a->{$field}->getOffsetTimestamp() : '';
								$b_date = $b->{$field} ? $b->{$field}->getOffsetTimestamp() : '';

								if ( $a_date === $b_date ) {
									return 0;
								}

								if ( ! $a_date ) {
									return 1;
								}

								if ( ! $b_date ) {
									return - 1;
								}

								return $a_date - $b_date;
							}
							elseif ( 'supplier' === $field ) {

								$a_supplier = new Supplier( $a->supplier_id );
								$b_supplier = new Supplier( $b->supplier_id );

								$a_value = empty( $a_supplier ) ? '' : strtolower( $a_supplier->name );
								$b_value = empty( $b_supplier ) ? '' : strtolower( $b_supplier->name );

								return 'asc' === strtolower( $order ) ? strcmp( $a_value, $b_value ) : strcmp( $b_value, $a_value );
							}
							elseif ( is_numeric( $a->{$field} ) ) {

								$a_value = floatval( $a->{$field} );
								$b_value = floatval( $b->{$field} );

								if ( $a_value > $b_value ) {
									return 'asc' === strtolower( $order ) ? 1 : - 1;
								}
								elseif ( $a_value < $b_value ) {
									return 'asc' === strtolower( $order ) ? - 1 : 1;
								}
								else {
									return 0;
								}
							} else {

								$a_value = empty( $a->{$field} ) ? '' : strtolower( $a->{$field} );
								$b_value = empty( $b->{$field} ) ? '' : strtolower( $b->{$field} );

								return 'asc' === strtolower( $order ) ? strcmp( $a_value, $b_value ) : strcmp( $b_value, $a_value );
							}

						} );

						break;

					// First added sells first.
					case 'fifo':
					default:
						usort( $inventories, function ( $a, $b ) {

							$a_date = $a->inventory_date ? $a->inventory_date->getOffsetTimestamp() : '';
							$b_date = $b->inventory_date ? $b->inventory_date->getOffsetTimestamp() : '';

							if ( $a_date === $b_date ) {
								return 0;
							}

							if ( ! $a_date ) {
								return 1;
							}

							if ( ! $b_date ) {
								return - 1;
							}

							return $a_date - $b_date;

						} );

						break;

				}

				// if $skip_cache, MI will be asking for all product prices in all zones.
				if ( ( ! is_admin() && ! AtumHelpers::is_rest_request() ) || $skip_cache ) {

					// If there is a region restriction mode enabled, get rid of the inventories out of scope of the customer's region.
					$region_restriction_mode = self::get_region_restriction_mode();

					// Countries mode.
					if ( 'countries' === $region_restriction_mode ) {

						$visitor_location = self::get_visitor_location();

						// Should we add the default region to all the inventories with no region assigned?
						$default_country = [];
						if ( 'yes' === AtumHelpers::get_option( 'mi_default_country_for_empty_regions', 'no' ) ) {
							$default_country[] = AtumHelpers::get_option( 'mi_default_country', NULL );
						}

						// We couldn't find the visitor's location, we should ask for it.
						if ( empty( $visitor_location['country'] ) && empty( $default_country ) ) {
							$inventories = [];
						}

						foreach ( $inventories as $inventory_index => $inventory ) {

							$region = $inventory->region;

							// Maybe use the default country?
							if ( empty( $region ) && ! empty( $default_country ) ) {
								$region = $default_country;
							}

							if ( ! is_array( $region ) || ! in_array( $visitor_location['country'], $region ) ) {
								unset( $inventories[ $inventory_index ] );
							}

						}

					}
					// Shipping Zones mode.
					elseif ( 'shipping-zones' === $region_restriction_mode ) {

						$visitor_location = self::get_visitor_location();

						if ( ! empty( array_filter( $visitor_location ) ) ) {
							$shipping_zones = self::get_zones_matching_package( [ 'destination' => $visitor_location ], 'ids', $skip_cache );
						}
						// If the visitor location can not be obtained, get the default zone.
						else {
							$shipping_zones = array_filter( [ AtumHelpers::get_option( 'mi_default_shipping_zone', '' ) ] );
						}

						// Should we add the default region to all the inventories with no region assigned?
						$default_zone = [];
						if ( 'yes' === AtumHelpers::get_option( 'mi_default_zone_for_empty_regions', 'no' ) ) {
							$default_zone[] = AtumHelpers::get_option( 'mi_default_shipping_zone', NULL );
						}

						// If there are no shipping zones matching the customer's address the product will be "Out of Stock".
						if ( empty( $shipping_zones ) && empty( $default_zone ) ) {
							$inventories = [];
						}

						foreach ( $inventories as $inventory_index => $inventory ) {

							$region = $inventory->region;

							// Maybe use the default zone?
							if ( empty( $region ) && ! empty( $default_zone ) ) {
								$region = $default_zone;
							}

							if ( ! is_array( $region ) || empty( $region ) || empty( array_intersect( $shipping_zones, $region ) ) ) {
								unset( $inventories[ $inventory_index ] );
							}

						}

					}

				}

			}

			$inventories = apply_filters( 'atum/multi_inventory/product_inventories_sorted', $inventories, $product_id, $not_write_off );

			if ( ! $skip_cache ) {
				AtumCache::set_cache( $cache_key, $inventories, ATUM_MULTINV_TEXT_DOMAIN );
			}

		}

		return $inventories;

	}

	/**
	 * Get the vistitor's location
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force_geolocation  Optional. Used when wanting to force the user geolocation when the "Shipping Zones" restriction mode is on.
	 * @param bool $force_return       Optional. Whether to force the location return bypassing the id_admin check.
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public static function get_visitor_location( $force_geolocation = FALSE, $force_return = FALSE ) {

		// Bypass the WP backend and the REST API calls.
		if ( ( is_admin() || AtumHelpers::is_rest_request() ) && ! $force_return ) {
			return [];
		}

		$visitor_location = array();

		// As this function could be called multiple times during the same request, let's cache it.
		if ( is_callable( array( WC()->session, 'get_customer_id' ) ) ) {
			$cache_key        = AtumCache::get_cache_key( 'visitor_location', WC()->session->get_customer_id() );
			$visitor_location = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );
		}
		else {
			$has_cache = FALSE;
		}

		if ( ! $has_cache ) {

			// TODO: IF A LOGGED IN USER HAS THE GEO COOKIE SET WITH A LOCATION DISTINCT TO HIS WC SHIPPING DATA, WHICH SHOULD GET?
			$visitor_location = array(
				'country'  => '',
				'state'    => '',
				'postcode' => '',
			);

			// Try to get the current customer's country.
			if ( is_user_logged_in() ) {
				$customer                     = new \WC_Customer( get_current_user_id(), TRUE );
				$visitor_location             = wc_format_country_state_string( $customer->get_shipping_country() . ':' . $customer->get_shipping_state() );
				$visitor_location['postcode'] = $customer->get_shipping_postcode();
			}

			if ( empty( $visitor_location['country'] ) ) {

				// Check if the user has the Geo Cookie.
				$atum_location = GeoPrompt::get_location_cookie();

				if ( ! empty( $atum_location ) ) {

					if ( ! empty( $atum_location['region'] ) ) {
						$visitor_location = array_merge( $visitor_location, self::explode_formatted_region( $atum_location['region'] ) );
					}

					if ( ! empty( $atum_location['postcode'] ) ) {
						$visitor_location['postcode'] = wc_normalize_postcode( $atum_location['postcode'] );
					}

				}
				// Try to geolocate the visitor.
				// Based on "wc_get_customer_default_location" function but forcing a Geolocation.
				else {

					$region_restriction_mode = self::get_region_restriction_mode();

					// When the "Shipping Zones" restriction mode is enabled and there is a default zone set, do not geolocate.
					if ( ! $force_geolocation && 'shipping-zones' === $region_restriction_mode && '' !== AtumHelpers::get_option( 'mi_default_shipping_zone', '' ) ) {
						return [];
					}

					// Exclude common bots from geolocation by user agent.
					$ua = wc_get_user_agent();

					if ( ! strstr( $ua, 'bot' ) && ! strstr( $ua, 'spider' ) && ! strstr( $ua, 'crawl' ) ) {

						// As external geolocation APIs may affect the performance, only enable it when needed.
						$enable_api_fallback = FALSE;

						if ( version_compare( WC()->version, '3.9.0', '>=' ) && 'countries' === $region_restriction_mode ) {

							$maxmind_options = get_option( 'woocommerce_maxmind_geolocation_settings' );

							if ( empty( $maxmind_options['license_key'] ) ) {
								$enable_api_fallback = TRUE;
							}

						}

						$visitor_location = \WC_Geolocation::geolocate_ip( '', TRUE, $enable_api_fallback );

					}

					// Shop Base fallback.
					if ( empty( $visitor_location['country'] ) ) {
						$base_country     = apply_filters( 'atum/multi_inventory/customer_default_country', AtumHelpers::get_option( 'mi_default_country', get_option( 'woocommerce_default_country' ) ) );
						$visitor_location = wc_format_country_state_string( $base_country );
					}

				}

			}

			$visitor_location = apply_filters( 'atum/multi_inventory/visitor_location', $visitor_location );

			// Save the it to cache to improve the performance.
			if ( isset( $cache_key ) ) {
				AtumCache::set_cache( $cache_key, $visitor_location, ATUM_MULTINV_TEXT_DOMAIN );
			}

		}

		return is_array( $visitor_location ) ? $visitor_location : [];

	}

	/**
	 * Get the customer's location
	 *
	 * @since 1.5.4
	 *
	 * @param int $customer_id
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public static function get_customer_location( $customer_id ) {

		// Bypass the WP backend and the REST API calls.
		if ( ! is_admin() && ! AtumHelpers::is_rest_request() ) {
			return [];
		}

		$customer_location = array();

		// Try to get the customer's country.
		$customer                      = new \WC_Customer( $customer_id, TRUE );
		$customer_location             = wc_format_country_state_string( $customer->get_shipping_country() . ':' . $customer->get_shipping_state() );
		$customer_location['postcode'] = $customer->get_shipping_postcode();

		$customer_location = apply_filters( 'atum/multi_inventory/customer_location', $customer_location, $customer_id );

		return is_array( $customer_location ) ? $customer_location : [];

	}

	/**
	 * Set the visitor location cache manually.
	 * Only used when we need to force it when the users change their location (from the checkout for example)
	 *
	 * @since 1.2.1.3
	 *
	 * @param array $location
	 */
	public static function set_visitor_location( $location ) {

		if ( is_callable( array( WC()->session, 'get_customer_id' ) ) ) {
			$cache_key = AtumCache::get_cache_key( 'visitor_location', WC()->session->get_customer_id() );
			AtumCache::set_cache( $cache_key, $location, ATUM_MULTINV_TEXT_DOMAIN );
		}

	}

	/**
	 * It gets a formatted region (for example ES:V) and it returns an associative array with the values
	 *
	 * @since 1.0.1
	 *
	 * @param string $formatted_region
	 *
	 * @return array
	 */
	public static function explode_formatted_region( $formatted_region ) {

		$formatted_region = strtoupper( wc_clean( $formatted_region ) );
		$visitor_location = array();

		if ( FALSE !== strpos( $formatted_region, ':' ) ) {
			list( $visitor_location['country'], $visitor_location['state'] ) = explode( ':', $formatted_region );
		}
		else {
			$visitor_location['country'] = $formatted_region;
		}

		return $visitor_location;

	}

	/**
	 * Get all the shipping zones matching a package
	 * This is a variation of the \WC_Shipping_Zones::get_zone_matching_package() method to return all the matching zones
	 *
	 * @since 1.0.0
	 *
	 * @param array  $package       The shipping package (array with destination address).
	 * @param string $return        The return format. Possible values: 'ids' or 'zones'.
	 * @param bool   $skip_cache    Optional. Whether to skip the cache. It's used to calculate inventories for distinct zones within the same process.
	 *
	 * @return array
	 */
	public static function get_zones_matching_package( $package, $return = 'ids', $skip_cache = FALSE ) {

		$cache_key      = AtumCache::get_cache_key( 'zones_matching_package', [ $package, $return ] );
		$has_cache      = FALSE;
		$matching_zones = [];

		if ( ! $skip_cache ) {
			$matching_zones = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );
		}

		if ( $has_cache ) {
			return $matching_zones;
		}

		global $wpdb;

		$country   = isset( $package['destination']['country'] ) ? strtoupper( wc_clean( $package['destination']['country'] ) ) : '';
		$state     = isset( $package['destination']['state'] ) ? strtoupper( wc_clean( $package['destination']['state'] ) ) : '';
		$continent = strtoupper( wc_clean( WC()->countries->get_continent_code_for_country( $country ) ) );
		$postcode  = isset( $package['destination']['postcode'] ) ? wc_normalize_postcode( wc_clean( $package['destination']['postcode'] ) ) : '';

		// Work out criteria for our zone search.
		$criteria   = array();
		$criteria[] = $wpdb->prepare( "( ( location_type = 'country' AND location_code = %s )", $country );
		$criteria[] = $wpdb->prepare( "OR ( location_type = 'state' AND location_code = %s )", $country . ':' . $state );
		$criteria[] = $wpdb->prepare( "OR ( location_type = 'continent' AND location_code = %s )", $continent );
		$criteria[] = 'OR ( location_type IS NULL ) )';

		// Postcode range and wildcard matching.
		$postcode_locations = $wpdb->get_results( "SELECT zone_id, location_code FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE location_type = 'postcode';" );

		if ( $postcode_locations ) {
			$zone_ids_with_postcode_rules = array_map( 'absint', wp_list_pluck( $postcode_locations, 'zone_id' ) );
			$matches                      = wc_postcode_location_matcher( $postcode, $postcode_locations, 'zone_id', 'location_code', $country );
			$do_not_match                 = array_unique( array_diff( $zone_ids_with_postcode_rules, array_keys( $matches ) ) );

			if ( ! empty( $do_not_match ) ) {
				$criteria[] = 'AND zones.zone_id NOT IN (' . implode( ',', $do_not_match ) . ')';
			}
		}

		// Get matching zones.
		// phpcs:disable WordPress.DB.PreparedSQL
		$matching_zone_ids = $wpdb->get_col( "
			SELECT zones.zone_id FROM {$wpdb->prefix}woocommerce_shipping_zones as zones
			LEFT OUTER JOIN {$wpdb->prefix}woocommerce_shipping_zone_locations as locations ON zones.zone_id = locations.zone_id AND location_type != 'postcode'
			WHERE " . implode( ' ', $criteria ) . '
			ORDER BY zone_order ASC, zone_id ASC
		');
		// phpcs:enable

		if ( 'ids' === $return ) {
			$matching_zones = $matching_zone_ids;
		}
		else {

			$matching_zones = array();

			if ( ! empty( $matching_zone_ids ) ) {

				foreach ( $matching_zone_ids as $matching_zone_id ) {
					$matching_zones[] = new \WC_Shipping_Zone( $matching_zone_id ?: 0 );
				}

			}

		}

		// Shop Base fallback.
		if ( empty( $matching_zones ) ) {

			$base_shipping_zone = absint( apply_filters( 'atum/multi_inventory/customer_default_zone', AtumHelpers::get_option( 'mi_default_shipping_zone', '' ) ) );

			if ( $base_shipping_zone ) {
				$zone = \WC_Shipping_Zones::get_zone( $base_shipping_zone );
				if ( $zone ) {
					$matching_zones[] = 'ids' === $return ? $zone->get_id() : $zone;
				}

			}

		}

		$matching_zones = (array) apply_filters( 'atum/multi_inventory/zones_matching_package', $matching_zones, $package );

		if ( ! $skip_cache ) {
			AtumCache::set_cache( $cache_key, $matching_zones, ATUM_MULTINV_TEXT_DOMAIN );
		}

		return $matching_zones;

	}

	/**
	 * Reduce stock levels for items within an WC order (ATUM orders not allowed)
	 * This is a hacked version of the 'wc_reduce_stock_levels' function
	 *
	 * Executed:
	 * - During the checkout process if paid or if the payment is an offline payment.
	 * - When changing to status complete, on-hold, processing from any other status.
	 * - When bulk-changing to status complete, on-hold, processing from any other status.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Order $order_id Order ID or order instance.
	 *
	 * @throws \Exception
	 */
	public static function reduce_stock_levels( $order_id ) {

		if ( $order_id instanceof \WC_Order ) {
			$order = $order_id;
		}
		else {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) || ! apply_filters( 'atum/multi_inventory/can_reduce_order_stock', TRUE, $order ) ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */

			// Only for products items.
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			$product            = $item->get_product();
			$product_id         = $product->get_id();
			$item_id            = $item->get_id();
			$item_stock_reduced = $item->get_meta( '_reduced_stock', TRUE );
			$changes            = array();

			// Only reduce stock once for each item (this was introduced in WC 3.5).
			if ( ( version_compare( WC()->version, '3.5', '>=' ) && $item_stock_reduced ) || ! $product instanceof \WC_Product ) {
				continue;
			}

			// As this is being executed when switching the orders' status from backend,
			// we have to try to get the inventory order items from db.
			$order_item_inventories = Inventory::get_order_item_inventories( $item_id );

			// Is Multi-inventory enabled?
			// NOTE: There could be the case of orders created before enabling MI
			// having products that didn't have MI enabled by then but now they do so.
			// So we have to bypass these products.
			if (
				! empty( $order_item_inventories ) &&
				'yes' === self::get_product_multi_inventory_status( $product ) &&
				self::is_product_multi_inventory_compatible( $product )
			) {

				$item_name           = $product->get_formatted_name();
				$total_reduced_stock = 0;

				foreach ( $order_item_inventories as $order_item_inventory ) {

					$inventory = self::get_inventory( $order_item_inventory->inventory_id );

					// If the call comes from the frontend or changing status, reduce all no previously reduced qty (qty - reduced_stock).
					$qty                  = $order_item_inventory->qty - $order_item_inventory->reduced_stock;
					$total_reduced_stock += $qty;

					// Trigger changes only if it remains not reduced stock.
					if ( $qty ) {

						// Change the reduced stock.
						$data = [ 'reduced_stock' => $order_item_inventory->qty ];

						$old_stock = $inventory->stock_quantity;
						$inventory->save_order_item_inventory( $item_id, $product_id, $data );

						// Reduce the stock.
						$new_stock = self::update_inventory_stock( $product, $inventory, $qty, 'decrease' );

						if ( is_wp_error( $new_stock ) || is_null( $new_stock ) && $inventory->managing_stock() || FALSE === $new_stock ) {
							/* translators: the first is the inventory name and second is the item name */
							$note_id = $order->add_order_note( sprintf( __( 'Unable to reduce inventory &quot;%1$s&quot; stock for item %2$s.', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->name, $item_name ) );
							AtumHelpers::save_order_note_meta( $note_id, [
								'action'       => 'unable_reduce',
								'item_name'    => $item->get_name(),
								'product_id'   => $product->get_id(),
								'inventory_id' => $inventory->id,
							] );
							continue;
						}

						// WC doesn't add notes for unmanaged products.
						if ( $inventory->managing_stock() ) {

							$changes[] = array(
								'product'   => $product,
								'inventory' => $inventory,
								'from'      => $old_stock,
								'to'        => $new_stock,
							);

						}

						// Update inventory's calculated order data.
						self::update_order_item_inventories_sales_calc_props( $inventory );
					}

				}

				$item->add_meta_data( '_reduced_stock', $total_reduced_stock, TRUE );
				$item->save();

				self::trigger_inventory_stock_change_notifications( $order, $changes );

			}
			// Multi-Inventory disabled or order item added to the order before enabling MI.
			// Just the default functionality (migrated from WC's original function).
			elseif ( $product->managing_stock() ) {

				$qty       = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
				$item_name = $product->get_formatted_name();
				$new_stock = wc_update_product_stock( $product, $qty, 'decrease' );

				if ( is_wp_error( $new_stock ) ) {
					/* translators: the item name */
					$note_id = $order->add_order_note( sprintf( __( 'Unable to reduce stock for item %s.', ATUM_MULTINV_TEXT_DOMAIN ), $item_name ) );
					AtumHelpers::save_order_note_meta( $note_id, [
						'action'     => 'unable_reduce',
						'item_name'  => $item_name,
						'product_id' => $product->get_id(),
					] );
					continue;
				}

				$item->add_meta_data( '_reduced_stock', $qty, TRUE );
				$item->save();

				$changes[] = array(
					'product' => $product,
					'from'    => $new_stock + $qty,
					'to'      => $new_stock,
				);

				/* @noinspection PhpDeprecationInspection */
				self::wc_trigger_stock_change_notifications( $order, $changes );

			}

		}

		// Leave here the WC action for 3rd party plugins compatibility.
		do_action( 'woocommerce_reduce_order_stock', $order );
		do_action( 'atum/multi_inventory/reduce_order_stock', $order );

	}

	/**
	 * Process inventories list by customer location restriction.
	 *
	 * @since 1.5.4
	 *
	 * @param array     $inventories
	 * @param \WC_Order $order
	 *
	 * @return array
	 */
	public static function check_product_inventories_by_customer_location( $inventories, $order ) {

		if ( ! is_admin() || empty( $order ) || empty( $inventories ) )
			return $inventories;

		// If there is a region restriction mode enabled, get rid of the inventories out of scope of the customer's region.
		$region_restriction_mode = self::get_region_restriction_mode();

		if ( 'no-restriction' === $region_restriction_mode ) {
			return $inventories;
		}

		$customer_location = self::get_customer_location( $order->get_customer_id() );

		// Countries mode.
		if ( 'countries' === $region_restriction_mode ) {

			// Should we add the default region to all the inventories with no region assigned?
			if ( 'yes' === AtumHelpers::get_option( 'mi_default_country_for_empty_regions', 'no' ) ) {
				$default_country[] = AtumHelpers::get_option( 'mi_default_country', NULL );
			}

			// We couldn't find the visitor's location, we should ask for it.
			if ( empty( $customer_location['country'] ) && empty( $default_country ) ) {
				$inventories = [];
			}

			foreach ( $inventories as $inventory_index => $inventory ) {

				$region = $inventory->region;

				// Maybe use the default country?
				if ( empty( $region ) && ! empty( $default_country ) ) {
					$region = $default_country;
				}

				if ( ! is_array( $region ) || ! in_array( $customer_location['country'], $region ) ) {
					unset( $inventories[ $inventory_index ] );
				}

			}

		}
		// Shipping Zones mode.
		elseif ( 'shipping-zones' === $region_restriction_mode ) {

			if ( ! empty( array_filter( $customer_location ) ) ) {
				$shipping_zones = self::get_zones_matching_package( [ 'destination' => $customer_location ], 'ids', $skip_cache );
			}
			// If the visitor location can not be obtained, get the default zone.
			else {
				$shipping_zones = array_filter( [ AtumHelpers::get_option( 'mi_default_shipping_zone', '' ) ] );
			}

			// Should we add the default region to all the inventories with no region assigned?
			$default_zone = [];
			if ( 'yes' === AtumHelpers::get_option( 'mi_default_zone_for_empty_regions', 'no' ) ) {
				$default_zone[] = AtumHelpers::get_option( 'mi_default_shipping_zone', NULL );
			}

			// If there are no shipping zones matching the customer's address the product will be "Out of Stock".
			if ( empty( $shipping_zones ) && empty( $default_zone ) ) {
				$inventories = [];
			}

			foreach ( $inventories as $inventory_index => $inventory ) {

				$region = $inventory->region;

				// Maybe use the default zone?
				if ( empty( $region ) && ! empty( $default_zone ) ) {
					$region = $default_zone;
				}

				if ( ! is_array( $region ) || empty( $region ) || empty( array_intersect( $shipping_zones, $region ) ) ) {
					unset( $inventories[ $inventory_index ] );
				}

			}

		}

		return $inventories;
	}

	/**
	 * Prepare the Multi-Inventory order items when an order is processed
	 *
	 * @since 1.2.0
	 *
	 * @param \WC_Order $order      The WC order to check.
	 * @param int       $item_only  Optional. Whether to get the data only for the specified item ID.
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public static function prepare_order_items_inventories( $order, $item_only = NULL ) {

		$items                 = $order->get_items();
		$inventory_order_items = array();
		$order_type            = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();
		$order_type_table_id   = Globals::get_order_type_table_id( $order_type );

		// Get inventories discount from cache if set.
		$cache_key             = AtumCache::get_cache_key( 'inventories_discounts' );
		$inventories_discounts = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE );

		$allow_change_stock = in_array( AtumHelpers::get_raw_wc_order_status( $order->get_status() ), Globals::get_order_statuses_change_stock() );

		foreach ( $items as $item ) {

			if ( $item_only && $item->get_id() !== $item_only ) {
				continue;
			}

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			$product_id = $item->get_variation_id() ?: $item->get_product_id();
			$product    = AtumHelpers::get_atum_product( $product_id );

			if ( 'yes' !== self::get_product_multi_inventory_status( $product ) || ! self::is_product_multi_inventory_compatible( $product ) ) {
				continue;
			}

			// Leave here the WC filter for 3rd party plugins compatibility.
			$qty = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
			$qty = apply_filters( 'atum/multi_inventory/order_item_quantity', $qty, $order, $item );

			$item_subtotal = $item->get_subtotal();
			$item_discount = $item_subtotal - $item->get_total();

			$has_multi_price     = self::has_multi_price( $product );
			$inventory_iteration = self::get_product_inventory_iteration( $product );
			$inventories         = apply_filters( 'atum/multi_inventory/order_item_inventories', self::get_product_inventories_sorted( $product_id ), $item );

			if ( is_admin() && empty( Inventory::get_order_item_inventories( $item->get_id(), $order_type_table_id ) ) ) {
				$inventories = self::check_product_inventories_by_customer_location( $inventories, $order );
			}

			// Check whether to use only selected inventories.
			$selected_mi = ! is_admin() ? self::get_cart_product_selected_inventories( $product ) : [];

			// If inventory iteration is not allowed, get only the first one.
			if ( empty( $selected_mi ) && 'out_of_stock' === $inventory_iteration ) {
				$inventories = array( reset( $inventories ) );
			}

			$changed_qty = $total_reduced_stock = 0;

			// Loop all the product inventories.
			foreach ( $inventories as $inventory ) {

				/**
				 * Variable definition
				 *
				 * @var Inventory $inventory
				 */

				// Once the stock is completely deducted, stop looping.
				if ( $changed_qty == $qty ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					break;
				}

				// If there are selected inventories, get the configuration from them.
				if ( ! empty( $selected_mi ) && ! array_key_exists( $inventory->id, $selected_mi ) ) {
					continue;
				}

				if ( ! $inventory->is_sellable() ) {
					continue;
				}

				if ( $inventory->is_main() ) {
					$inventory->set_stock_status();
				}

				if ( empty( $selected_mi ) && 'outofstock' === $inventory->stock_status ) {
					continue;
				}

				if ( empty( $selected_mi ) ) {

					if ( $inventory->managing_stock() ) {

						// If the item has an "out of stock threshold" lower than the total stock quantity, use it as the max available stock.
						$inventory_stock = $inventory->get_available_stock();

						if ( $inventory_stock <= 0 && 'no' === $inventory->backorders ) {
							continue;
						}

						$reduce_qty = $qty - $changed_qty;

						if ( 'no' === $inventory->backorders && $inventory_stock <= $reduce_qty ) {
							$changed_qty += $inventory_stock;
							$reduce_qty   = $inventory_stock;
						}
						else {
							$changed_qty += $reduce_qty;
						}

					}
					else {

						// Not managing stock and "in stock" or "backordered". All remaining qty is taken from this inventory.
						$reduce_qty  = $qty - $changed_qty;
						$changed_qty = $qty;
					}

				}
				else {

					$reduce_qty   = $selected_mi[ $inventory->id ];
					$changed_qty += $selected_mi[ $inventory->id ];

				}

				$total_reduced_stock += $reduce_qty;

				if ( $has_multi_price ) {

					$price  = $inventory->price;
					$totals = $sub_totals = wc_get_price_excluding_tax( $product, array(
						'price' => $price,
						'qty'   => $reduce_qty,
					) );

					if ( is_callable( array( WC()->cart, 'get_coupons' ) ) ) {

						$coupons = WC()->cart->get_coupons();

						/**
						 * Cart's coupon's.
						 *
						 * @var \WC_Coupon $coupon
						 */
						foreach ( $coupons as $code => $coupon ) {

							if ( ! empty( $inventories_discounts[ $code ][ $inventory->id ] ) ) {
								$totals -= wc_get_price_excluding_tax( $product, [ 'price' => $inventories_discounts[ $code ][ $inventory->id ] ] );
							}
						}

					}

				}
				else {

					$price      = $product->get_price();
					$sub_totals = wc_get_price_excluding_tax( $product, array(
						'price' => $price,
						'qty'   => $reduce_qty,
					) );
					$totals     = $sub_totals * ( 1 - $item_discount / $item_subtotal );

				}

				// Add a new inventory order item line.
				$data = [
					'qty'           => $reduce_qty,
					'subtotal'      => $sub_totals,
					'total'         => $totals,
					'reduced_stock' => $allow_change_stock ? 0 : NULL,
				];

				// Inventory extra data.
				if ( $inventory->exists() ) {
					$data['extra_data'] = maybe_serialize( $inventory->prepare_order_item_inventory_extra_data( $item->get_id() ) );
				}

				$inventory_order_items[ $item->get_id() ][ $inventory->id ] = compact( 'inventory', 'data', 'product_id', 'product', 'total_reduced_stock' );

				// An inventory with Back Orders allowed would prevent the others from loading.
				if ( 'yes' === $inventory->backorders ) {
					break;
				}

			}

		}

		return $inventory_order_items;

	}

	/**
	 * Get a product's selected inventories from the cart.
	 *
	 * @since 1.3.8
	 *
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public static function get_cart_product_selected_inventories( $product ) {

		$selected_mi = [];

		$cart_contents = WC()->cart->cart_contents;

		if ( ! empty( $cart_contents ) ) {

			if ( $product->is_type( 'variation' ) ) {
				$cart_item = wp_list_filter( $cart_contents, [ 'variation_id' => $product->get_id() ] );
			}
			else {
				$cart_item = wp_list_filter( $cart_contents, [ 'product_id' => $product->get_id() ] );
			}

			if ( ! empty( $cart_item ) ) {

				$cart_item_key = key( $cart_item );

				if ( ! empty( $cart_item[ $cart_item_key ]['atum']['selected_mi'] ) ) {
					$selected_mi = $cart_item[ $cart_item_key ]['atum']['selected_mi'];
				}

			}

		}

		return $selected_mi;
	}

	// TODO: REFACTORY INCREASE AND REDUCE STOCK LEVELS.
	/**
	 * Increase stock levels for items within an order.
	 * This is a hacked version of the 'wc_increase_stock_levels' function
	 *
	 * Executed:
	 * - When changing order status from completed, on-hold, processing to others.
	 *
	 * @since 1.0.1
	 *
	 * @param int|\WC_Order $order_id Order ID or order instance.
	 *
	 * @throws \Exception
	 */
	public static function increase_stock_levels( $order_id ) {

		if ( $order_id instanceof \WC_Order ) {
			$order = $order_id;
		}
		else {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) || ! apply_filters( 'atum/multi_inventory/can_restore_order_stock', TRUE, $order ) ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */

			// Only for products items.
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			$product            = $item->get_product();
			$item_id            = $item->get_id();
			$item_stock_reduced = $item->get_meta( '_reduced_stock', TRUE );
			$changes            = array();

			// Only reduce stock once for each item.
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			// As this is being executed when switching the orders' status from backend,
			// we have to try to get the inventory order items from db.
			$order_item_inventories = Inventory::get_order_item_inventories( $item_id );

			if ( ! $item_stock_reduced ) {

				if ( empty( $order_item_inventories ) ) {
					continue;
				}

				$inv_stock_reduced = 0;

				foreach ( $order_item_inventories as $order_item_inventory ) {
					$inv_stock_reduced += (float) $order_item_inventory->reduced_stock;
				}

				if ( ! $inv_stock_reduced ) {
					continue;
				}

				$item_stock_reduced = $inv_stock_reduced;
			}

			$item_name = $product->get_formatted_name();

			// Is Multi-Inventory enabled?
			// NOTE: There could be the case of orders created before enabling MI
			// having products that didn't have MI enabled by then but now they do so.
			// So we have to bypapass these products.
			if (
				! empty( $order_item_inventories ) &&
				'yes' === self::get_product_multi_inventory_status( $product ) &&
				self::is_product_multi_inventory_compatible( $product )
			) {

				$changes[] = " [$item_name]";

				foreach ( $order_item_inventories as $order_item_inventory ) {

					$inventory = self::get_inventory( $order_item_inventory->inventory_id );

					if ( 'no' === $inventory->manage_stock ) {
						continue;
					}

					// Set reduced stock to 0.
					$data = [ 'reduced_stock' => NULL ];
					$inventory->save_order_item_inventory( $order_item_inventory->order_item_id, $order_item_inventory->inventory_id, $data );

					$old_stock = $inventory->stock_quantity;
					$new_stock = self::update_inventory_stock( $product, $inventory, $order_item_inventory->qty, 'increase' );

					if ( is_wp_error( $new_stock ) || is_null( $new_stock ) || FALSE === $new_stock ) {
						/* translators: first one is the inventory name and second is the item name */
						$note_id = $order->add_order_note( sprintf( __( 'Unable to restore stock for inventory %1$s of item %2$s.', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->name, $item_name ) );
						AtumHelpers::save_order_note_meta( $note_id, [
							'action'       => 'unable_restore',
							'item_name'    => $item_name,
							'product_id'   => $product->get_id(),
							'inventory_id' => $inventory->id,
						] );
						continue;
					}

					// Update inventory's calculated order data.
					self::update_order_item_inventories_sales_calc_props( $inventory );

					/* translators: the inventory name */
					$changes[] = ' ' . $old_stock . '&rarr;' . $new_stock . ' ' . sprintf( __( 'using inventory &quot;%s&quot;', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->name );

				}

			}
			// Multi-Inventory disabled or order item added to the order before enabling MI.
			// Just the default functionality (migrated from WC's original function).
			elseif ( $product->managing_stock() ) {

				$item_name = $product->get_formatted_name();
				$new_stock = wc_update_product_stock( $product, $item_stock_reduced, 'increase' );

				if ( is_wp_error( $new_stock ) ) {
					/* translators: the item name */
					$note_id = $order->add_order_note( sprintf( __( 'Unable to restore stock for item %s.', ATUM_MULTINV_TEXT_DOMAIN ), $item_name ) );
					AtumHelpers::save_order_note_meta( $note_id, [
						'action'     => 'unable_restore',
						'item_name'  => $item_name,
						'product_id' => $product->get_id(),
					] );
					continue;
				}

				$changes[] = $item_name . ' ' . ( $new_stock - $item_stock_reduced ) . '&rarr;' . $new_stock;

			}

			$item->delete_meta_data( '_reduced_stock' );
			$item->save();

			if ( ! empty( $changes ) ) {
				$note_id = $order->add_order_note( __( 'Stock levels increased:', ATUM_MULTINV_TEXT_DOMAIN ) . ' ' . implode( ', ', $changes ) );
				AtumHelpers::save_order_note_meta( $note_id, [
					'action'     => 'stock_levels_increased',
					'item_name'  => $item_name,
					'product_id' => $product->get_id(),
					'changes'    => $changes,
				] );
			}

		}

		// Leave here the WC action for 3rd party plugins compatibility.
		do_action( 'woocommerce_restore_order_stock', $order );
		do_action( 'atum/multi_inventory/restore_order_stock', $order );

	}

	/**
	 * Change stock levels for items within an ATUM order.
	 *
	 * @since 1.5.0
	 *
	 * @param int    $order_id
	 * @param string $action
	 *
	 * @throws \Exception
	 */
	public static function atum_order_change_stock_levels( $order_id, $action = 'increase' ) {

		$order = AtumHelpers::get_atum_order_model( $order_id, TRUE );

		if ( ! is_wp_error( $order ) ) {

			$post_type = $order->get_post_type();

			if (

				( PurchaseOrders::POST_TYPE === $post_type && ! AtumCapabilities::current_user_can( 'edit_purchase_order' ) ) ||
				( InventoryLogs::POST_TYPE === $post_type && ! AtumCapabilities::current_user_can( 'edit_inventory_log' ) ) ||
				! current_user_can( 'edit_shop_orders' )
			) {
				return;
			}

			$order_items = $order->get_items();

			foreach ( $order_items as $item_id => $order_item ) {

				/**
				 * Each order product line
				 *
				 * @var \WC_Order_Item_Product $order_item
				 */
				if ( ! $order_item->is_type( 'line_item' ) ) {
					continue;
				}

				// On completed POs, do not change the stock if it was already changed.
				if (
					PurchaseOrders::POST_TYPE === $post_type && PurchaseOrders::FINISHED === $order->get_status() &&
					'yes' === $order_item->get_stock_changed()
				) {
					continue;
				}

				/**
				 * Variable definition.
				 * WPML NOTE: Don't need original translation because orders don't have languages.
				 *
				 * @var \WC_Product $product
				 */
				$product = $order_item->get_product();

				if ( $product instanceof \WC_Product && $product->exists() ) {

					$changes   = array();
					$item_name = $product->get_formatted_name();

					// Get the inventory order items to change.
					$order_item_inventories = Inventory::get_order_item_inventories( $order_item->get_id(), 2 );

					// Is Multi-Inventory enabled?
					// NOTE: There could be the case of ATUM orders created before enabling MI
					// having products that didn't have MI enabled by then but now they do so.
					// So we have to bypapass these products.
					if (
						! empty( $order_item_inventories ) &&
						'yes' === self::get_product_multi_inventory_status( $product ) &&
						self::is_product_multi_inventory_compatible( $product )
					) {

						$changes[] = " [$item_name]";

						foreach ( $order_item_inventories as $inventory_order_item ) {

							$inventory = self::get_inventory( $inventory_order_item->inventory_id );

							if ( 'no' === $inventory->manage_stock ) {
								continue;
							}

							$old_stock = $inventory->stock_quantity;
							$new_stock = self::update_inventory_stock( $product, $inventory, $inventory_order_item->qty, $action );

							if ( is_wp_error( $new_stock ) || is_null( $new_stock ) && $inventory->managing_stock() || FALSE === $new_stock ) {
								/* translators: first one is the inventory name and second is the item name */
								$note_id = $order->add_order_note( sprintf( __( 'Unable to %1$s stock for inventory %2$s of item %3$s.', ATUM_MULTINV_TEXT_DOMAIN ), $action, $inventory->name, $item_name ) );
								AtumHelpers::save_order_note_meta( $note_id, [
									'action'       => "unable_{$action}_stock",
									'item_name'    => $order_item->get_name(),
									'product_id'   => $product->get_id(),
									'inventory_id' => $inventory->id,
								] );
								continue;
							}

							if ( $inventory->managing_stock() ) {
								/* translators: the inventory name */
								$changes[] = ' ' . $old_stock . '&rarr;' . $new_stock . ' ' . sprintf( __( 'using inventory &quot;%s&quot;', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->name );
							}

						}

					}
					// Multi-Inventory disabled or order item added to the order before enabling MI.
					// Just the default functionality (migrated from WC's original function).
					elseif ( $product->managing_stock() ) {

						$item_name = $product->get_formatted_name();
						$new_stock = wc_update_product_stock( $product, $order_item->get_quantity(), $action );

						if ( is_wp_error( $new_stock ) ) {
							/* translators: the item name */
							$note_id = $order->add_order_note( sprintf( __( 'Unable to %1$s stock for item %2$s.', ATUM_MULTINV_TEXT_DOMAIN ), $action, $item_name ) );
							AtumHelpers::save_order_note_meta( $note_id, [
								'action'     => "unable_{$action}_stock",
								'item_name'  => $order_item->get_name(),
								'product_id' => $product->get_id(),
							] );
							continue;
						}

						$old_stock = 'increase' === $action ? $new_stock - $order_item->get_quantity() : $new_stock + $order_item->get_quantity();

						$changes[] = $item_name . ' ' . $old_stock . '&rarr;' . $new_stock;

					}

					if ( ! empty( $changes ) ) {
						/* translators: the action done */
						$note_id = $order->add_order_note( sprintf( __( 'Stock levels %s:', ATUM_MULTINV_TEXT_DOMAIN ), $action . 'd' ) . ' ' . implode( ', ', $changes ) );
						AtumHelpers::save_order_note_meta( $note_id, [
							'action'   => "stock_levels_{$action}",
							'changes'  => $changes,
						] );

						// Make sure the stock change is registered to the inherent item.
						$order_item->set_stock_changed( TRUE );
						$order_item->save();
					}

				}
			}

			do_action( 'atum/multi_inventory/after_atum_order_change_stock_levels', $order );

		}
	}


	/**
	 * After stock change events, triggers emails and adds order notes.
	 *
	 * @since 1.0.1
	 *
	 * @param \WC_Order $order   Order object.
	 * @param array     $changes Array of changes.
	 */
	public static function trigger_inventory_stock_change_notifications( $order, $changes ) {

		if ( empty( $changes ) ) {
			return;
		}

		$order_notes      = array();
		$no_stock_amount  = absint( get_option( 'woocommerce_notify_no_stock_amount', 0 ) );
		$low_stock_amount = get_option( 'woocommerce_notify_low_stock_amount' );
		$v350             = version_compare( WC()->version, '3.5.0', '>=' );

		foreach ( $changes as $change ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Product $product
			 * @var Inventory   $inventory
			 */
			$product   = $change['product'];
			$inventory = $change['inventory'];

			$order_notes[] = ' [' . $product->get_formatted_name() . ']';

			// The 'wc_get_low_stock_amount' was added in WC 3.5.0.
			if ( $v350 ) {
				$low_stock_amount = absint( wc_get_low_stock_amount( $product ) );
			}

			/* translators: the inventory name */
			$order_notes[] = ' ' . $change['from'] . '&rarr;' . $change['to'] . ' ' . sprintf( __( 'using inventory &quot;%s&quot;', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->name );

			// Cache data for inventory stock notifications.
			$cache_key = AtumCache::get_cache_key( 'product_inventories_notify_stock', $product->get_id() );
			$data      = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE );

			if ( $change['to'] <= $no_stock_amount ) {
				// Store data for inventory stock notifications.
				$data[ $inventory->id ]['no_stock'] = time();
				AtumCache::set_cache( $cache_key, $data, ATUM_MULTINV_TEXT_DOMAIN );

				do_action( 'woocommerce_no_stock', $product );
				do_action( 'atum/multi_inventory/no_stock', $product, $change['inventory'] );
			}
			elseif ( '' !== $low_stock_amount && $change['to'] <= $low_stock_amount ) {
				// Store data for inventory stock notifications.
				$data[ $inventory->id ]['low_stock'] = time();
				AtumCache::set_cache( $cache_key, $data, ATUM_MULTINV_TEXT_DOMAIN );

				do_action( 'woocommerce_low_stock', $product );
				do_action( 'atum/multi_inventory/low_stock', $product, $inventory );
			}

			if ( $change['to'] < 0 ) {

				$backorder_args = array(
					'product'  => $product,
					'order_id' => $order->get_id(),
					'quantity' => abs( $change['from'] - $change['to'] ),
				);

				// Store data for inventory stock notifications.
				$data[ $inventory->id ]['backorder'] = time();
				AtumCache::set_cache( $cache_key, $data, ATUM_MULTINV_TEXT_DOMAIN );

				do_action( 'woocommerce_product_on_backorder', $backorder_args );
				do_action( 'atum/multi_inventory/product_on_backorder', $backorder_args, $inventory );

			}

		}

		/* translators: the item name */
		$note_id = $order->add_order_note( __( 'Stock levels reduced:', ATUM_MULTINV_TEXT_DOMAIN ) . ' ' . implode( ', ', $order_notes ) );
		AtumHelpers::save_order_note_meta( $note_id, [
			'action'   => "stock_levels_reduced",
			'changes'  => $changes,
		] );

	}

	/**
	 * After stock change events, triggers emails and adds order notes.
	 *
	 * @since 1.0.0
	 *
	 * @deprecated This method is a clone of the 'wc_trigger_stock_change_notifications' function to be compatible with versions older than 3.5.
	 *
	 * @param \WC_Order $order   Order object.
	 * @param array     $changes Array of changes.
	 */
	private static function wc_trigger_stock_change_notifications( $order, $changes ) {

		if ( empty( $changes ) ) {
			return;
		}

		$order_notes      = array();
		$no_stock_amount  = absint( get_option( 'woocommerce_notify_no_stock_amount', 0 ) );
		$low_stock_amount = get_option( 'woocommerce_notify_low_stock_amount' );
		$v350             = version_compare( WC()->version, '3.5.0', '>=' );

		foreach ( $changes as $change ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Product $product
			 */
			$product = $change['product'];

			$order_notes[] = $product->get_formatted_name() . ' ' . $change['from'] . '&rarr;' . $change['to'];
			if ( $v350 ) {
				$low_stock_amount = absint( wc_get_low_stock_amount( $product ) );
			}

			if ( $change['to'] <= $no_stock_amount ) {
				do_action( 'woocommerce_no_stock', $product );
			}
			elseif ( '' !== $low_stock_amount && $change['to'] <= $low_stock_amount ) {
				do_action( 'woocommerce_low_stock', $product );
			}

			if ( $change['to'] < 0 ) {

				do_action( 'woocommerce_product_on_backorder', array(
					'product'  => $product,
					'order_id' => $order->get_id(),
					'quantity' => abs( $change['from'] - $change['to'] ),
				) );

			}

		}

		$note_id = $order->add_order_note( __( 'Stock levels reduced:', ATUM_MULTINV_TEXT_DOMAIN ) . ' ' . implode( ', ', $order_notes ) );
		AtumHelpers::save_order_note_meta( $note_id, [
			'action'   => "stock_levels_reduced",
			'changes'  => $changes,
		] );
	}

	/**
	 * Update a product's stock amount
	 * This is a hacked version of the 'wc_update_product_stock' function
	 *
	 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues)
	 *
	 * @since  1.0.0
	 *
	 * @param  int|\WC_Product $product   Product ID or product instance. Must be original translation.
	 * @param  Inventory       $inventory The Inventory object that will update.
	 * @param  int|null        $item_qty  Optional. Item quantity.
	 * @param  string          $operation Optional. Type of opertion, allows 'set', 'increase' and 'decrease'.
	 *
	 * @return float|bool|null
	 *
	 * @throws \Exception
	 */
	public static function update_inventory_stock( $product, $inventory, $item_qty = NULL, $operation = 'set' ) {

		if ( ! apply_filters( 'atum/multi_inventory/maybe_update_inventory_stock_from_order', TRUE, $inventory, $product, $item_qty ) ) {
			return FALSE;
		}

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product instanceof \WC_Product ) {
			return FALSE;
		}

		add_filter( 'atum/multi_inventory/bypass_mi_get_manage_stock', '__return_true' );
		$is_main           = $inventory->is_main();
		$is_managing_stock = $is_main ? $product->managing_stock() : 'yes' === $inventory->manage_stock;

		if ( ! is_null( $item_qty ) && $is_managing_stock ) {

			// Some products (variations) can have their stock managed by their parent. Get the correct ID to reduce here.
			// TODO: TAKE CARE OF THIS WHEN HANDLING STOCK FOR VARIABLES AND VARIATIONS AND GET THE RIGHT INVENTORY ID.
			$product_id_with_stock = apply_filters( 'atum/multi_inventory/product_id', $product->get_stock_managed_by_id() );

			switch ( $operation ) {
				case 'increase':
					$new_stock = $inventory->stock_quantity + $item_qty;
					break;

				case 'decrease':
					$new_stock = $inventory->stock_quantity - $item_qty;
					break;

				default:
					$new_stock = $item_qty;
					break;
			}

			$inventory->set_meta( array( 'stock_quantity' => $new_stock ) );

			// Main Inventory (WC).
			if ( $is_main ) {

				/* @noinspection PhpUndefinedClassInspection */
				$data_store = \WC_Data_Store::load( 'product' );

				/* @noinspection PhpUndefinedClassInspection */
				/**
				 * Variable definition
				 *
				 * @var \WC_Product_Data_Store_CPT $data_store
				 */
				$data_store->update_product_stock( $product_id_with_stock, $item_qty, $operation );

			}
			// Custom Inventories.
			else {
				$inventory->save_meta();
			}

			delete_transient( 'wc_low_stock_count' );
			delete_transient( 'wc_outofstock_count' );
			delete_transient( 'wc_product_children_' . ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) );
			wp_cache_delete( "product-{$product_id_with_stock}", 'products' );

			// Prevent product meta to be deleted when updating stock.
			if ( is_admin() ) {

				// If WPML is enabled, it must be the original translation.
				remove_action( 'save_post_product', array( ProductMetaBoxes::get_instance(), 'save_meta_boxes' ), 100 );
				remove_action( 'woocommerce_save_product_variation', array( ProductMetaBoxes::get_instance(), 'save_meta_boxes' ), 100 );
			}

			// Re-read product data after updating stock, then have stock status calculated and saved.
			$product_with_stock = wc_get_product( $product_id_with_stock );
			$timestamp          = AtumHelpers::get_current_timestamp();
			$product_with_stock->set_date_modified( $timestamp );
			$product_with_stock->save();

			if ( is_admin() ) {
				ProductMetaBoxes::get_instance()->register_product_meta_boxes_hooks();
			}

			if ( $product_with_stock->is_type( 'variation' ) ) {
				do_action( 'atum/multi_inventory/before_variation_set_stock', $product_with_stock, $inventory );
				do_action( 'woocommerce_variation_set_stock', $product_with_stock );
				do_action( 'atum/multi_inventory/variation_set_stock', $product_with_stock, $inventory );
			}
			else {
				do_action( 'atum/multi_inventory/before_product_set_stock', $product_with_stock, $inventory );
				do_action( 'woocommerce_product_set_stock', $product_with_stock );
				do_action( 'atum/multi_inventory/product_set_stock', $product_with_stock, $inventory );
			}

			// WC product bundles compatibility.
			if ( class_exists( '\WC_Product_Bundle' ) ) {
				self::bundled_product_stock_changed( $product, $inventory->stock_quantity );
			}

			$return_stock = $inventory->stock_quantity;

		}
		else {

			$return_stock = $is_main ? $product->get_stock_quantity() : $inventory->stock_quantity;
		}

		remove_filter( 'atum/multi_inventory/bypass_mi_get_manage_stock', '__return_true' );

		return $return_stock;

	}

	/**
	 * Get all the set of region combinations that are being used within the multi-inventories
	 *
	 * @since 1.0.0
	 *
	 * @param string $region_mode   Whether to get the country or shipping zone regions.
	 *
	 * @return array
	 */
	public static function get_used_regions( $region_mode ) {

		global $wpdb;

		$formatted_regions = array();

		switch ( $region_mode ) {
			case 'countries':
				$regions = $wpdb->get_col( "SELECT DISTINCT region FROM $wpdb->prefix" . Inventory::INVENTORIES_TABLE . " WHERE region != ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( ! empty( $regions ) ) {

					$regions = array_map( 'maybe_unserialize', $regions );

					$countries_arr = self::get_regions( 'countries' );
					$country_codes = array_keys( $countries_arr );

					foreach ( $regions as $region ) {

						$formatted_region = array();

						if ( is_array( $region ) ) {
							foreach ( $region as $r ) {
								if ( in_array( $r, $country_codes ) ) {
									$formatted_region[ $r ] = $countries_arr[ $r ];
								}
							}
						}

						$formatted_regions[] = $formatted_region;

					}
				}

				break;

			case 'shipping-zones':
				// phpcs:disable WordPress.DB.PreparedSQL
				$regions = $wpdb->get_results("
		  			SELECT ir.zone_id, zone_name, inventory_id 
					FROM $wpdb->prefix" . Inventory::INVENTORY_REGIONS_TABLE . " ir
					INNER JOIN {$wpdb->prefix}woocommerce_shipping_zones ws ON ir.zone_id = ws.zone_id							
					ORDER BY region_order ASC
				");
				// phpcs:enable

				if ( ! empty( $regions ) ) {

					// Group the results by inventory.
					$regions = AtumHelpers::array_group_by( $regions, 'inventory_id' );

					foreach ( $regions as $region_set ) {
						// Set the zone_id as key and zone_name as value.
						$formatted_regions[] = array_combine( wp_list_pluck( $region_set, 'zone_id' ), wp_list_pluck( $region_set, 'zone_name' ) );
					}

					// Discard repeated sets of zones.
					$formatted_regions = array_unique( $formatted_regions, SORT_REGULAR );

				}

				break;

		}

		return $formatted_regions;

	}

	/**
	 * Get all the regions used for specific products.
	 *
	 * @since 1.3.5
	 *
	 * @param array  $product_ids The product ID. If WPML is installed, must be original translation.
	 * @param string $region_mode Whether to get the country or shipping zone regions.
	 *
	 * @return array
	 */
	public static function get_product_used_regions( $product_ids, $region_mode ) {

		global $wpdb;

		$regions_arr = [];

		switch ( $region_mode ) {
			case 'countries':
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
				$regions = $wpdb->get_col( "
					SELECT DISTINCT region FROM $wpdb->prefix" . Inventory::INVENTORIES_TABLE . " 
					WHERE region != '' AND product_id IN (" . implode( ',', $product_ids ) . ')
				' );
				// phpcs:enable

				if ( ! empty( $regions ) ) {

					$regions = array_map( 'maybe_unserialize', $regions );

					foreach ( $regions as $region ) {

						if ( is_array( $region ) ) {
							foreach ( $region as $r ) {

								if ( ! in_array( $r, $regions_arr ) ) {
									$regions_arr[] = $r;
								}
							}
						}

					}
				}

				break;

			case 'shipping-zones':
				// phpcs:disable WordPress.DB.PreparedSQL
				$regions_arr = $wpdb->get_col( "
		  			SELECT DISTINCT ir.zone_id 
					FROM $wpdb->prefix" . Inventory::INVENTORY_REGIONS_TABLE . " ir
					INNER JOIN {$wpdb->prefix}" . Inventory::INVENTORIES_TABLE . ' it ON ir.inventory_id = it.id							
					WHERE it.product_id IN (' . implode( ',', $product_ids ) . ')
				' );
				// phpcs:enable

				break;

		}

		$use_default_option = 'countries' === $region_mode ? 'mi_default_country_for_empty_regions' : 'mi_default_zone_for_empty_regions';

		if ( 'yes' === AtumHelpers::get_option( $use_default_option, 'no' ) ) {

			$default_zone_option = 'countries' === $region_mode ? 'mi_default_country' : 'mi_default_shipping_zone';
			$default_zone_id     = AtumHelpers::get_option( $default_zone_option, '' );

			if ( $default_zone_id && ! in_array( $default_zone_id, $regions_arr ) ) {
				$regions_arr[] = $default_zone_id;
			}
		}

		return $regions_arr;

	}

	/**
	 * Replace some merge tags in the passed text to valid HTML tags
	 *
	 * @since 1.0.0
	 *
	 * @param string $text
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public static function replace_text_tags( $text ) {

		$text = str_replace( '[br]', '<br>', $text );
		$text = str_replace( '[em]', '<em>', $text );
		$text = str_replace( '[/em]', '</em>', $text );
		$text = str_replace( '[strong]', '<strong>', $text );
		$text = str_replace( '[/strong]', '</strong>', $text );

		if ( FALSE !== strpos( $text, '[country]' ) ) {

			$default_country_name = '';
			$geolocation          = self::get_visitor_location( TRUE ); // Force the geolocation.

			if ( ! empty( $geolocation['country'] ) ) {
				$default_country_code = $geolocation['country'];
				$country_list         = self::get_regions( 'countries' );
				$default_country_name = isset( $country_list[ $default_country_code ] ) ? $country_list[ $default_country_code ] : '';
			}

			$text = str_replace( '[country]', "<strong>$default_country_name</strong>", $text );

		}

		return $text;

	}

	/**
	 * Replace the [link] and [/link] tags with a link to the privacy page set in WooCommerce settings
	 * If no tags are found in the text, the whole text will be wrapped with the link
	 *
	 * @since 1.0.0
	 *
	 * @param string $privacy_text
	 * @param string $privacy_page
	 *
	 * @return string
	 */
	public static function replace_privacy_link_tags( $privacy_text, $privacy_page = '' ) {

		if ( $privacy_text ) {

			if ( ! $privacy_page ) {
				// The 'wc_privacy_policy_page_id' function was introduced in WC 3.4.0.
				$privacy_page = function_exists( 'wc_privacy_policy_page_id' ) ? get_permalink( wc_privacy_policy_page_id() ) : esc_url( AtumHelpers::get_option( 'mi_geoprompt_privacy_page' ) );
			}

			if ( ! $privacy_page ) {
				return '';
			}

			$privacy_link       = '<a href="' . $privacy_page . '" title="' . __( 'Privacy Policy', ATUM_MULTINV_TEXT_DOMAIN ) . '" target="_blank">';
			$privacy_link_close = '</a>';

			if ( FALSE === strpos( $privacy_text, '[link]' ) ) {
				$privacy_text = $privacy_link . $privacy_text . $privacy_link_close;
			}
			else {
				$privacy_text = str_replace( '[link]', $privacy_link, $privacy_text );
				$privacy_text = str_replace( '[/link]', $privacy_link_close, $privacy_text );
			}

		}

		return $privacy_text;

	}

	/**
	 * Getter for the region restriction mode option
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_region_restriction_mode() {
		return AtumHelpers::get_option( 'mi_region_restriction_mode', 'no-restriction' );
	}

	/**
	 * Get a comma separated list of region labels for the specified region IDs
	 *
	 * @since 1.0.1
	 *
	 * @param array $region_ids
	 *
	 * @return string
	 */
	public static function get_region_labels( $region_ids ) {

		$region_restriction_mode = self::get_region_restriction_mode();
		$regions                 = self::get_regions( $region_restriction_mode );

		if ( ! empty( $region_ids ) && is_array( $region_ids ) ) {

			foreach ( $region_ids as $region_id ) {

				// Countries mode.
				if ( 'countries' === $region_restriction_mode ) {

					if ( isset( $regions[ $region_id ] ) ) {
						$region_labels[] = $regions[ $region_id ];
					}

				}
				// Shipping Zones mode.
				else {

					$zone = wp_list_filter( $regions, [ 'id' => $region_id ] );
					if ( ! empty( $zone ) ) {
						$region_labels[] = current( $zone )['zone_name'];
					}

				}

			}

		}

		// Check if we should use the default region for all the inventories with no region assigned.
		$no_region = __( 'None', ATUM_MULTINV_TEXT_DOMAIN );
		if ( empty( $region_labels ) ) {

			$use_default_option = 'countries' === $region_restriction_mode ? 'mi_default_country_for_empty_regions' : 'mi_default_zone_for_empty_regions';

			if ( 'yes' === AtumHelpers::get_option( $use_default_option, 'no' ) ) {

				$default_zone_option = 'countries' === $region_restriction_mode ? 'mi_default_country' : 'mi_default_shipping_zone';
				$default_zone_id     = AtumHelpers::get_option( $default_zone_option, '' );

				if ( $default_zone_id ) {

					$default_zone_label = '';

					if ( 'countries' === $region_restriction_mode ) {

						if ( isset( $regions[ $default_zone_id ] ) ) {
							$default_zone_label = $regions[ $default_zone_id ];
						}

					}
					else {

						$zone = wp_list_filter( $regions, [ 'id' => $default_zone_id ] );
						if ( ! empty( $zone ) ) {
							$default_zone_label = current( $zone )['zone_name'];
						}

					}

					if ( $default_zone_label ) {
						$no_region .= ' <span>(' . $default_zone_label . ')</span>';
					}

				}

			}

		}

		return ! empty( $region_labels ) ? implode( ', ', $region_labels ) : $no_region;

	}

	/**
	 * Get all the available regions for the specified region restriction mode
	 *
	 * @since 1.3.4
	 *
	 * @param string $region_restriction_mode It can be 'shipping-zones' or 'countries'.
	 *
	 * @return array
	 */
	public static function get_regions( $region_restriction_mode ) {

		// Avoid duplicated queries using cache.
		$cache_key = AtumCache::get_cache_key( 'get_mi_regions', [ $region_restriction_mode ] );
		$regions   = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $regions;
		}

		switch ( $region_restriction_mode ) {
			case 'shipping-zones':
				global $wpdb;
				$raw_zones = $wpdb->get_results( "SELECT zone_id, zone_name, zone_order FROM {$wpdb->prefix}woocommerce_shipping_zones order by zone_order ASC, zone_id ASC;" );
				$regions   = [];

				foreach ( $raw_zones as $raw_zone ) {

					$regions[ $raw_zone->zone_id ] = [
						'id'        => $raw_zone->zone_id,
						'zone_id'   => $raw_zone->zone_id,
						'zone_name' => $raw_zone->zone_name,
					];

				}
				break;

			case 'countries':
				$regions = WC()->countries->get_countries();
				break;

			default:
				$regions = array();
				break;
		}

		AtumCache::set_cache( $cache_key, $regions, ATUM_MULTINV_TEXT_DOMAIN );

		return $regions;

	}

	/**
	 * Get a comma separated list of ATUM location labels for the specified location IDs
	 *
	 * @since 1.0.1
	 *
	 * @param array $location_ids
	 *
	 * @return string
	 */
	public static function get_location_labels( $location_ids ) {

		$locations = get_terms(
			array(
				'taxonomy'   => Globals::PRODUCT_LOCATION_TAXONOMY,
				'hide_empty' => FALSE,
			)
		);

		if ( ! empty( $location_ids ) && is_array( $location_ids ) ) {

			foreach ( $location_ids as $location_id ) {

				if ( ! empty( $location_term = wp_list_filter( $locations, [ 'term_id' => $location_id ] ) ) ) {
					$location_labels[] = current( $location_term )->name;
				}

			}

		}

		return ! empty( $location_labels ) ? implode( ', ', $location_labels ) : __( 'None', ATUM_MULTINV_TEXT_DOMAIN );

	}

	/**
	 * Get a prop for the active inventory (next to be sold) of the specified product
	 *
	 * @since 1.0.1
	 *
	 * @param string $prop
	 * @param mixed  $value
	 * @param int    $product_id
	 * @param bool   $check_stock_status
	 *
	 * @return mixed|NULL
	 *
	 * @throws \Exception
	 */
	public static function get_first_inventory_prop( $prop, $value, $product_id, $check_stock_status = TRUE ) {

		// Use cache to improve performance.
		$cache_key            = AtumCache::get_cache_key( "product_{$prop}", [ $product_id, $check_stock_status ] );
		$inventory_prop_value = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $inventory_prop_value;
		}

		// The previous step has checked for the proper product type (has_multi_price).
		$inventories          = self::get_product_inventories_sorted( $product_id );
		$inventory_prop_value = NULL;

		if ( ! empty( $inventories ) ) {

			// Iterate all the inventories in the sorting mode order until finding the first in stock (if any).
			foreach ( $inventories as $inventory ) {

				if ( ! $inventory->is_sellable() ) {
					continue;
				}

				if ( $inventory->is_main() ) {

					$inventory->set_stock_status();

					if ( ! $check_stock_status || in_array( $inventory->stock_status, [ 'instock', 'onbackorder' ], TRUE ) ) {
						return $value;
					}

				}
				elseif ( ! $check_stock_status || in_array( $inventory->stock_status, [ 'instock', 'onbackorder' ], TRUE ) ) {
					$inventory_prop_value = $inventory->{$prop};
					break;
				}

			}

		}
		// If no inventories, return the Main Inventory property.
		else {
			$inventory            = Inventory::get_product_main_inventory( $product_id );
			$inventory_prop_value = $inventory->{$prop};
		}

		$inventory_prop_value = ! is_wp_error( $inventory_prop_value ) ? $inventory_prop_value : NULL;
		$inventory_prop_value = apply_filters( "atum/multi_inventory/get_first_inventory_prop_{$prop}", $inventory_prop_value, $product_id );

		AtumCache::set_cache( $cache_key, $inventory_prop_value, ATUM_MULTINV_TEXT_DOMAIN );

		return $inventory_prop_value;

	}

	/**
	 * Returns whether a product (or any of its children) has multi inventory enabled.
	 *
	 * @since 1.0.1
	 *
	 * @param int $product_id
	 *
	 * @return bool
	 */
	public static function has_multi_inventory( $product_id ) {

		// Use cache to improve performance.
		$cache_key = AtumCache::get_cache_key( 'product_has_multi_inventory', $product_id );
		$result    = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			$product = wc_get_product( $product_id );

			if ( ! $product instanceof \WC_Product ) {
				return FALSE;
			}

			$result = FALSE;
			$type   = $product->get_type();

			// Check the product type.
			if ( in_array( $type, MultiInventory::get_compatible_parent_types() ) ) {

				$child_products = $product->get_children();

				if ( ! empty( $child_products ) ) {
					foreach ( $child_products as $child_id ) {

						// TODO: Decide: if the parent is mi enabled, also are enabled all the child?
						if ( 'yes' === self::get_product_multi_inventory_status( $child_id ) ) {
							$result = TRUE;
							break;
						}

					}
				}

			}
			elseif ( MultiInventory::is_mi_compatible_product_type( $type ) ) {
				$result = 'yes' === self::get_product_multi_inventory_status( $product );
			}

			AtumCache::set_cache( $cache_key, wc_bool_to_string( $result ), ATUM_MULTINV_TEXT_DOMAIN );

		}
		else {
			$result = wc_string_to_bool( $result );
		}

		return $result;
	}

	/**
	 * Check if the specified product is multi-price-ready
	 *
	 * @since 1.0.1
	 *
	 * @param \WC_Product|int $product
	 *
	 * @return bool
	 */
	public static function has_multi_price( $product ) {

		$product_id      = $product instanceof \WC_Product ? $product->get_id() : $product;
		$cache_key       = AtumCache::get_cache_key( 'has_multi_price', $product_id );
		$has_multi_price = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			if ( ! $product instanceof \WC_Product ) {
				$product = wc_get_product( $product );
			}

			$has_multi_price = (
				$product instanceof \WC_Product && 'yes' === self::get_product_multi_inventory_status( $product ) &&
				self::is_product_multi_inventory_compatible( $product ) && 'yes' === self::get_product_price_per_inventory( $product ) &&
				MultiInventory::is_mi_compatible_product_type( $product->get_type() )
			);

			AtumCache::set_cache( $cache_key, $has_multi_price, ATUM_MULTINV_TEXT_DOMAIN );

		}

		return $has_multi_price;

	}

	/**
	 * Get the inbound stock amount for the specified inventory
	 *
	 * @param Inventory $inventory The inventory to check.
	 * @param bool      $force     Optional. Whether to force the recalculation from db.
	 *
	 * @return int|float
	 * @since 1.2.0
	 */
	public static function get_inventory_inbound_stock( &$inventory, $force = FALSE ) {

		$cache_key     = AtumCache::get_cache_key( 'inventory_inbound_stock', $inventory->id );
		$inbound_stock = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache || $force ) {

			// Check if the inbound stock is already saved on the ATUM inventories table.
			$inbound_stock = $inventory->inbound_stock;

			if ( ! is_numeric( $inbound_stock ) || $force ) {

				// Get all the valid statuses excluding the finished.
				$statuses = array_diff( array_keys( PurchaseOrders::get_statuses() ), [ PurchaseOrders::FINISHED ] );

				// Calculate the inbound stock from pending purchase orders.
				global $wpdb;

				// phpcs:disable WordPress.DB.PreparedSQL
				$sql = $wpdb->prepare( "
					SELECT SUM(io.`qty`) AS quantity 			
					FROM $wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . " AS oi 			
					LEFT JOIN $wpdb->prefix" . Inventory::INVENTORY_ORDERS_TABLE . " AS io ON oi.`order_item_id` = io.`order_item_id`
					LEFT JOIN $wpdb->posts AS p ON oi.`order_id` = p.`ID`
					WHERE p.`post_type` = %s AND io.`product_id` = %d 
					AND p.`post_status` IN ('" . implode( "','", $statuses ) . "') AND io.inventory_id = %d AND io.order_type = %d					
					GROUP BY io.`product_id`",
					PurchaseOrders::POST_TYPE,
					$inventory->product_id,
					$inventory->id,
					Globals::get_order_type_table_id( PurchaseOrders::POST_TYPE )
				);
				// phpcs:enable

				$inbound_stock = wc_stock_amount( $wpdb->get_var( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				// Save it for future quicker access.
				$inventory->set_data( [ 'inbound_stock' => $inbound_stock ] );

			}

			AtumCache::set_cache( $cache_key, $inbound_stock, ATUM_MULTINV_TEXT_DOMAIN );

		}

		return $inbound_stock;

	}

	/**
	 * Get the inventory items' sales since $date_start or between $date_start and $date_end
	 *
	 * @since 1.0.1
	 *
	 * @param array|int $items      Array of Inventory IDs (or single ID) we want to calculate sales from.
	 * @param int       $date_start The date from when to start the inventory items' sales calculations (must be a string format convertible with strtotime).
	 * @param int       $date_end   Optional. The max date to calculate the inventory items' sales (must be a string format convertible with strtotime).
	 * @param array     $colums     Optional. Which columns to return from DB. Possible values: "qty", "total" and "inv_id".
	 *
	 * @return array
	 */
	public static function get_inventory_sold_last_days( $items, $date_start, $date_end = NULL, $colums = [ 'qty' ] ) {

		$items_sold = array();

		if ( ! empty( $items ) && ! empty( $colums ) ) {

			// Avoid duplicated queries on Stock Central using cache.
			$cache_key  = AtumCache::get_cache_key( 'inventory_sold_last_days', [ $items, $date_start, $date_end, $colums ] );
			$items_sold = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

			if ( $has_cache ) {
				return $items_sold;
			}

			global $wpdb;

			// Prepare the SQL query to get the orders in the specified time window.
			$date_start = gmdate( 'Y-m-d H:i:s', strtotime( $date_start ) );
			$date_where = $wpdb->prepare( 'WHERE post_date_gmt >= %s', $date_start );

			if ( $date_end ) {
				$date_end    = gmdate( 'Y-m-d H:i:s', strtotime( $date_end ) );
				$date_where .= $wpdb->prepare( ' AND post_date_gmt <= %s', $date_end );
			}

			$orders_query = "
				SELECT ID FROM $wpdb->posts  
				$date_where
				AND post_type = 'shop_order' AND post_status IN ('wc-processing', 'wc-completed')				  
			";

			if ( is_array( $items ) ) {
				$inventories_where = 'IN (' . implode( ',', $items ) . ')';
			}
			else {
				$inventories_where = "= $items";
			}

			$inventory_orders_table = $wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE;
			$query_columns          = $query_joins = [];

			if ( in_array( 'qty', $colums ) ) {
				$query_columns[] = 'ABS( SUM(`inv`.`qty`) ) AS QTY';
				$query_joins[]   = "INNER JOIN $inventory_orders_table AS `inv` ON (`items`.`order_item_id` = `inv`.`order_item_id`)";
			}

			if ( in_array( 'total', $colums ) ) {
				$query_columns[] = 'SUM(`mt_total`.`meta_value`) AS TOTAL';
				$query_joins[]   = "INNER JOIN `$wpdb->order_itemmeta` AS `mt_total` ON (`mt_id`.`order_item_id` = `mt_total`.`order_item_id`) AND `mt_total`.`meta_key` = '_line_total'";
				$query_joins[]   = "INNER JOIN `$wpdb->order_itemmeta` AS `mt_id` ON (`items`.`order_item_id` = `mt_id`.`order_item_id`)";
			}

			if ( in_array( 'inv_id', $colums ) ) {
				$query_columns[] = '`inv`.`inventory_id` AS INV_ID';
			}

			$query_columns_str = implode( ', ', $query_columns );
			$query_joins_str   = implode( "\n", $query_joins );
			$order_type_id     = Globals::get_order_type_table_id();

			// TODO: IT'S GETTING THE LINE TOTAL AND, AS IT'S SPLITTED IN INVENTORIES, IT SHOULD BE DIVIDED BY THE INV. QTY.
			// phpcs:disable WordPress.DB.PreparedSQL
			$query = $wpdb->prepare("
				SELECT $query_columns_str
				FROM $wpdb->posts AS `orders`
			    INNER JOIN {$wpdb->prefix}woocommerce_order_items AS `items` ON (`orders`.`ID` = `items`.`order_id`)			  
			  	$query_joins_str			      			  
				WHERE `orders`.`ID` IN ($orders_query) AND `inv`.`inventory_id` $inventories_where AND `inv`.`order_type` = %d
				GROUP BY `inv`.`inventory_id`
				HAVING (QTY IS NOT NULL);
			", $order_type_id );
			// phpcs:enable

			// For single inventories.
			if ( ! is_array( $items ) || count( $items ) === 1 ) {

				// When only 1 single result is requested.
				if ( count( $colums ) === 1 ) {
					$items_sold = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
				// Multiple results requested.
				else {
					$items_sold = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}

			}
			// For multiple inventories.
			else {

				// When only 1 single result for each inventory is requested.
				if ( count( $colums ) === 1 ) {
					$items_sold = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
				// Multiple results requested for each inventory.
				else {
					$items_sold = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}

			}

			AtumCache::set_cache( $cache_key, $items_sold, ATUM_MULTINV_TEXT_DOMAIN );

		}

		return $items_sold;

	}

	/**
	 * Get the lost sales of a specified inventory during the last days
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory  The inventory to calculate the lost sales.
	 * @param int       $days       Optional. By default the calculation is made for 7 days average.
	 *
	 * @return bool|float Returns the lost sales or FALSE if never had lost sales
	 */
	public static function get_inventory_lost_sales( $inventory, $days = 7 ) {

		$lost_sales     = FALSE;
		$out_stock_date = $inventory->out_stock_date;

		if ( $out_stock_date && $days > 0 ) {

			$out_stock_days = self::get_inventory_out_stock_days( $inventory );

			if ( is_numeric( $out_stock_days ) && $out_stock_days > 0 ) {

				// Get the average sales for the past days when in stock.
				$days           = absint( $days );
				$sold_last_days = self::get_inventory_sold_last_days( $inventory->id, $out_stock_date . " -{$days} days", $out_stock_date );
				$lost_sales     = 0;

				if ( ! empty( $sold_last_days ) && ! empty( $sold_last_days['QTY'] ) && $sold_last_days['QTY'] > 0 ) {

					$average_sales = $sold_last_days['QTY'] / $days;

					if ( self::has_multi_price( $inventory->product_id ) ) {
						$price = $inventory->regular_price;
					}
					else {
						$product = wc_get_product( $inventory->product_id );

						if ( $product instanceof \WC_Product ) {

							$price = $product->get_regular_price();
						}
						else {

							$price = 0;
						}
					}

					$lost_sales = $out_stock_days * $average_sales * $price;

				}

			}

		}

		return $lost_sales;

	}

	/**
	 * Get the stock on hold amount for the specified inventory
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory
	 * @param bool      $force
	 *
	 * @return int|float
	 */
	public static function get_inventory_stock_on_hold( &$inventory, $force = FALSE ) {

		$cache_key     = AtumCache::get_cache_key( 'inventory_stock_on_hold', $inventory->id );
		$stock_on_hold = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache || $force ) {

			// Check if the inbound stock is already saved on the ATUM inventories table.
			$stock_on_hold = $inventory->stock_on_hold;

			if ( ! is_numeric( $stock_on_hold ) || $force ) {

				global $wpdb;

				$order_statuses = (array) apply_filters( 'atum/multi_inventory/get_inventory_stock_on_hold/order_statuses', [ 'wc-processing', 'wc-on-hold' ] );

				// phpcs:disable WordPress.DB.PreparedSQL
				$sql = $wpdb->prepare( "
					SELECT SUM(io.`qty`) AS qty 			
					FROM {$wpdb->prefix}woocommerce_order_items AS oi 															  
					LEFT JOIN $wpdb->prefix" . Inventory::INVENTORY_ORDERS_TABLE . " AS io ON oi.`order_item_id` = io.`order_item_id`
					WHERE oi.`order_id` IN (
						SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order' AND post_status IN ('" . implode( "','", $order_statuses ) . "')
					)
					AND io.`inventory_id` = %d AND io.`order_type` = %d
					GROUP BY io.`product_id`",
					$inventory->id,
					Globals::get_order_type_table_id()
				);
				// phpcs:enable

				$stock_on_hold = wc_stock_amount( $wpdb->get_var( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				// Save it for future quicker access.
				$inventory->set_data( [ 'stock_on_hold' => $stock_on_hold ] );

			}

			AtumCache::set_cache( $cache_key, $stock_on_hold, ATUM_MULTINV_TEXT_DOMAIN );

		}

		return $stock_on_hold;

	}

	/**
	 * Get the number of days that an inventory was "Out of Stock"
	 *
	 * @since 1.1.3
	 *
	 * @param Inventory $inventory
	 * @param bool      $force
	 *
	 * @return bool|int Returns the number of days or FALSE if is not "Out of Stock".
	 */
	public static function get_inventory_out_stock_days( $inventory, $force = FALSE ) {

		$out_stock_days = $inventory->out_stock_days;

		if ( ! is_numeric( $out_stock_days ) || $force ) {

			$out_stock_days = FALSE;

			// Check if the current product has the "Out of stock" date recorded.
			$out_stock_date = $inventory->out_stock_date;

			if ( $out_stock_date ) {

				try {
					$out_date_time = new \DateTime( $out_stock_date );
					$now_date_time = new \DateTime( 'now' );
					$interval      = date_diff( $out_date_time, $now_date_time );

					$out_stock_days = $interval->days;

				} catch ( \Exception $e ) {
					error_log( __METHOD__ . ' || Inventory: ' . $inventory->id . ' || ' . $e->getMessage() );

					return $out_stock_days;
				}

			}

		}

		return $out_stock_days;

	}

	/**
	 * Get the Inventory Log's inventory item quantity for a specific type of log
	 *
	 * @since 1.2.0
	 *
	 * @param string    $log_type     Type of log.
	 * @param Inventory $inventory    Inventory to check.
	 * @param string    $log_status   Optional. Log status (completed or pending).
	 * @param bool      $force        Optional. Force to retrieve the data from db.
	 *
	 * @return int|float
	 */
	public static function get_log_item_inventory_qty( $log_type, &$inventory, $log_status = 'pending', $force = FALSE ) {

		$log_types   = Log::get_log_type_columns();
		$column_name = isset( $log_types[ $log_type ] ) ? $log_types[ $log_type ] : '';

		if ( ! $force && $column_name && ! is_wp_error( $inventory->{$column_name} ) ) {
			$qty = $inventory->{$column_name};
		}

		if ( ! isset( $qty ) || ! is_numeric( $qty ) ) {

			$cache_key = AtumCache::get_cache_key( 'log_item_inventory_qty', [ $inventory->id, $log_type, $log_status ] );
			$qty       = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

			if ( ! $has_cache || $force ) {

				$log_ids = AtumHelpers::get_logs( $log_type, $log_status );

				if ( ! empty( $log_ids ) ) {

					global $wpdb;

					// Get the sum of quantities for the specified product in the logs of that type.
					// phpcs:disable WordPress.DB.PreparedSQL
					$query = $wpdb->prepare( "
						SELECT SUM(io.`qty`) 				  
					 	FROM $wpdb->prefix" . Inventory::INVENTORY_ORDERS_TABLE . " io
		                LEFT JOIN $wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . ' oi ON io.`order_item_id` = oi.`order_item_id`
					    WHERE oi.`order_id` IN (' . implode( ',', $log_ids ) . ') AND io.`order_type` = %d AND io.`inventory_id` = %d',
						Globals::get_order_type_table_id( ATUM_PREFIX . 'inventory_log' ),
						$inventory->id
					);
					// phpcs:enable

					$qty = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				}
				else {
					$qty = 0;
				}

				// Save it for future quicker access.
				if ( $column_name && ! is_wp_error( $inventory->{$column_name} ) ) {
					$inventory->set_data( [ $column_name => $qty ] );
				}

			}

			AtumCache::set_cache( $cache_key, $qty, ATUM_MULTINV_TEXT_DOMAIN );

		}

		return floatval( $qty );

	}

	/**
	 * Get documentation by order item
	 *
	 * @since 1.1.3
	 *
	 * @param int $order_id
	 *
	 * @return string
	 */
	public static function get_documentation_link( $order_id ) {

		$order_type = get_post_type( $order_id );

		switch ( $order_type ) {
			case PurchaseOrders::POST_TYPE:
				$link = 'https://forum.stockmanagementlabs.com/t/mi-purchase-orders';
				break;
			case InventoryLogs::POST_TYPE:
				$link = 'https://forum.stockmanagementlabs.com/t/mi-inventory-logs';
				break;
			default:
				$link = 'https://forum.stockmanagementlabs.com/t/mi-orders';
				break;
		}

		return $link;
	}

	/**
	 * Trigger bundled items stock meta refresh when product stock (status) changes.
	 *
	 * @since 1.0.7.7
	 *
	 * @param \WC_Product $product
	 * @param int         $stock_quantity
	 *
	 * @throws \Exception
	 */
	public static function bundled_product_stock_changed( $product, $stock_quantity = NULL ) {

		$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		$max_stock  = 0;

		// Do not apply to products that do not have the multi-inventory enabled.
		if ( 'yes' === self::get_product_multi_inventory_status( $product ) || self::is_product_multi_inventory_compatible( $product ) ) {

			$bundled_item_query_results = AtumHelpers::get_bundle_items( array(
				'product_id' => $product_id,
				'meta_query' => array(
					array(
						'key'  => 'quantity_min',
						'type' => 'NUMERIC',
					),
				),
			) );

			// Not a bundled product?
			if ( ! empty( $bundled_item_query_results ) ) {

				$bundled_item_ids = array_map( 'absint', wp_list_pluck( $bundled_item_query_results, 'bundled_item_id' ) );
				$bundle_ids       = array_map( 'absint', wp_list_pluck( $bundled_item_query_results, 'bundle_id' ) );
				$inventories      = self::get_product_inventories_sorted( $product_id );

				if ( ! $stock_quantity ) {
					$max_stock = 0;
				}

				$product_stock_status = 'out_of_stock';

				if ( ! empty( $inventories ) ) {

					$inventory_iteration = self::get_product_inventory_iteration( $product );

					// Iterate all the inventories in the sorting mode order until find the first in stock (if any).
					foreach ( $inventories as $inventory ) {

						if ( ! $inventory->is_sellable() ) {
							continue;
						}

						if ( 'use_next' === $inventory_iteration ) {
							$available_stock = $inventory->get_available_stock();

							if ( $available_stock ) {

								$product_stock_status = 'in_stock';

								if ( ! $stock_quantity ) {
									$max_stock += $available_stock;
								}

							}
						}
						// Get the first selling inventory.
						else {

							$first_inventory      = current( $inventories );
							$product_stock_status = $first_inventory->stock_status;
							break;
						}

					}

				}

				$stock_status = $product_stock_status;

				$data = array(
					'stock_status' => $stock_status,
					'max_stock'    => ! $stock_quantity ? $max_stock : $stock_quantity,
				);

				\WC_PB_DB::bulk_update_bundled_item_meta( $bundled_item_ids, 'max_stock', $data['max_stock'] );
				\WC_PB_DB::bulk_update_bundled_item_meta( $bundled_item_ids, 'stock_status', $data['stock_status'] );

				// Reset 'bundled_items_stock_status' on parent bundles.
				$data_store = \WC_Data_Store::load( 'product-bundle' );
				/* @noinspection PhpUndefinedMethodInspection */
				$data_store->reset_bundled_items_stock_status( $bundle_ids );

			}

		}

	}

	/**
	 * Checks if the passed inventory was not updated recently and requires a new update
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory    The inventory to check.
	 * @param string    $time_frame   Optional. A time string compatible with strtotime. By default is 1 day in the past.
	 *
	 * @return bool
	 */
	public static function is_inventory_data_outdated( $inventory, $time_frame = '-1 day' ) {

		return is_null( $inventory->update_date ) || strtotime( $inventory->update_date ) <= strtotime( $time_frame );
	}

	/**
	 * Update the expiring data (calculated props) for the specified inventory when updating an order item.
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory
	 * @param int       $order_type_id
	 */
	public static function update_order_item_inventories_sales_calc_props( $inventory, $order_type_id = 1 ) {

		if ( ! $inventory || ! $inventory->exists() )
			return;

		// Update the inventory extra props.
		switch ( $order_type_id ) {

			// Purchase Order.
			case 2:
				self::get_inventory_inbound_stock( $inventory, TRUE );
				break;

			// Inventory Log.
			case 3:
				foreach ( Log::get_log_type_columns() as $log_type => $log_type_column ) {
					self::get_log_item_inventory_qty( $log_type, $inventory, 'pending', TRUE );
				}
				break;

			// WooCommerce Order.
			default:
				$timestamp = AtumHelpers::get_current_timestamp();
				$day       = AtumHelpers::date_format( $timestamp, TRUE );
				$sale_days = AtumHelpers::get_sold_last_days_option();

				$sold_today       = self::get_inventory_sold_last_days( $inventory->id, 'today 00:00:00', $day );
				$sales_last_ndays = self::get_inventory_sold_last_days( $inventory->id, "$day -$sale_days days", $day );
				$lost_sales       = self::get_inventory_lost_sales( $inventory );
				$stock_on_hold    = self::get_inventory_stock_on_hold( $inventory, TRUE );

				$inventory->set_data( array(
					'sold_today'      => $sold_today,
					'sales_last_days' => $sales_last_ndays,
					'lost_sales'      => $lost_sales,
					'stock_on_hold'   => $stock_on_hold,
				) );
				break;
		}

		$inventory->save();

	}

	/**
	 * Whether the get product properties must be bypassed or not:
	 * - When is admin and is doing ATUM ajax calls.
	 * - When is admin but it isn't DOING_AJAX.
	 * - When is admin and is doing some specific WC's ajax calls.
	 *
	 * @since 1.2.1.7
	 *
	 * @return bool
	 */
	public static function bypass_product_properties_filters() {

		return is_admin() && (
				AtumHelpers::is_atum_ajax() || ! wp_doing_ajax() || (
					wp_doing_ajax() && ! empty( $_REQUEST['action'] ) &&
					in_array( $_REQUEST['action'], [ 'woocommerce_add_variation', 'woocommerce_save_variations', 'woocommerce_link_all_variations' ] )
				)
			);
	}

}
