<?php
/**
 * Legacy trait for Multi-Inventory's Hooks
 *
 * @package         AtumMultiInventory\Legacy
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @deprecated      This legacy class is only here for backwards compatibility and will be removed in a future version.
 *
 * @since           1.0.7
 */

namespace AtumMultiInventory\Legacy;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Models\Inventory;


trait HooksLegacyTrait {

	/**
	 * Add MI suppliers assigned to non-main inventories to supplier's products list
	 *
	 * @since 1.0.1
	 *
	 * @param array        $products
	 * @param \WP_Post     $supplier
	 * @param array|string $post_type
	 * @param bool         $type_filter
	 * @param array        $extra_filters
	 *
	 * @return array
	 */
	public function add_mi_supplier_products_legacy( $products, $supplier, $post_type, $type_filter, $extra_filters ) {

		global $wpdb;

		$supplier_products = $supplier_variations = $supplier_variables = $term_ids = array();

		$mi_meta_col        = 'apd.multi_inventory';
		$mi_meta_where      = 'yes' === AtumHelpers::get_option( 'mi_default_multi_inventory', 'no' ) ? "$mi_meta_col IS NULL" : "$mi_meta_col = 1";
		$atum_product_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		// Get all product IDs with the supplier assigned in an secondary inventory.
		$supplier_select = "
			SELECT DISTINCT i.product_id, p.post_parent
			FROM $wpdb->prefix" . Inventory::INVENTORIES_TABLE . " i
			INNER JOIN $atum_product_table apd ON (i.product_id = apd.product_id)
			INNER JOIN $wpdb->prefix" . Inventory::INVENTORY_META_TABLE . " im ON (i.id = im.inventory_id)
			INNER JOIN $wpdb->posts p ON (i.product_id = p.ID)
		";

		// phpcs:disable WordPress.DB.PreparedSQL
		$supplier_where = $wpdb->prepare( "
			WHERE $mi_meta_where
			AND im.supplier_id = %d
		", $supplier->ID );
		// phpcs:enable

		$term_join_products = $term_join_variations = $term_where = '';

		// Check the product type if needed.
		$is_filtering_product_type = FALSE;

		if ( ! empty( $extra_filters['tax_query'] ) ) {
			$product_types_filter      = wp_list_filter( $extra_filters['tax_query'], [ 'taxonomy' => 'product_type' ] );
			$is_filtering_product_type = ! empty( $product_types_filter );
		}

		// Product type filter.
		if ( $type_filter && ! $is_filtering_product_type ) {
			$product_types        = (array) apply_filters( 'atum/suppliers/supplier_product_types', Globals::get_product_types() );
			$term_ids             = AtumHelpers::get_term_ids_by_slug( $product_types, 'product_type' );
			$term_where           = ' AND tr.term_taxonomy_id IN (' . implode( ',', $term_ids ) . ')';
			$term_join_products   = " INNER JOIN $wpdb->term_relationships tr ON (p.ID = tr.object_id) ";
			$term_join_variations = " INNER JOIN $wpdb->term_relationships tr ON (p.post_parent = tr.object_id) ";
		}

		// Add any extra filter (product category for example).
		if ( ! empty( $extra_filters['tax_query'] ) && is_array( $extra_filters['tax_query'] ) ) {

			foreach ( $extra_filters['tax_query'] as $index => $tax_query ) {

				$term_ids              = AtumHelpers::get_term_ids_by_slug( (array) $tax_query['terms'], $tax_query['taxonomy'] );
				$term_join_products   .= " LEFT JOIN $wpdb->term_relationships tr$index ON (p.ID = tr$index.object_id) ";
				$term_join_variations .= " LEFT JOIN $wpdb->term_relationships tr$index ON (p.ID = tr$index.object_id) ";
				$term_where           .= " AND tr$index.term_taxonomy_id IN (" . implode( ',', $term_ids ) . ') ';

			}

		}

		if ( ( is_array( $post_type ) && in_array( 'product', $post_type, TRUE ) ) || 'product' === $post_type ) {
			$supplier_products = $wpdb->get_results( $supplier_select . $term_join_products . $supplier_where . $term_where . " AND p.post_type = 'product'", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$supplier_products = ! empty( $supplier_products ) ? wp_list_pluck( $supplier_products, 'product_id' ) : [];
		}

		if ( ( is_array( $post_type ) && in_array( 'product_variation', $post_type, TRUE ) ) || 'product_variation' === $post_type ) {

			$check_supplier_variations = TRUE;

			// When filtering by non-variable product type and only checking for variations, we should exclude them all.
			if ( $is_filtering_product_type && is_array( $post_type ) && ! in_array( 'product', $post_type, TRUE ) ) {

				$check_supplier_variations = FALSE;
				$product_type_filter       = current( $product_types_filter );

				if ( in_array( 'variable', $product_type_filter['terms'] ) !== FALSE ) {
					$check_supplier_variations = TRUE;
				}

			}

			if ( $check_supplier_variations ) {

				$supplier_variations = $wpdb->get_results( $supplier_select . $supplier_where . " AND p.post_type = 'product_variation'", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( ! empty( $supplier_variations ) ) {

					// phpcs:disable WordPress.DB.PreparedSQL
					$supplier_variables = $wpdb->get_col( "
						SELECT DISTINCT p.post_parent
						FROM $wpdb->posts p
						$term_join_variations
						WHERE p.ID IN (" . implode( ',', wp_list_pluck( $supplier_variations, 'product_id' ) ) . ")
						$term_where
					" );
					// phpcs:enable

					// Exclude all the variations belonging to not returned variables.
					array_filter( $supplier_variations, function ( $item ) use ( $supplier_variables ) {
						return in_array( $item['post_parent'], $supplier_variables ) ? $item : FALSE;
					} );

				}

				$supplier_variations = wp_list_pluck( $supplier_variations, 'product_id' );

			}

		}

		$products = array_unique( array_merge( $products, $supplier_products, $supplier_variables, $supplier_variations ) );

		return $products;

	}

}
