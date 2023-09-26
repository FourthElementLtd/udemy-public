/* =======================================
   MULTI-INVENTORY UI FOR ORDERS
   ======================================= */

import Blocker from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_blocker';
import Settings from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';
import Tooltip from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_tooltip';
import Utils from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/utils/_utils';
import WPHooks from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/interfaces/wp.hooks';

export default class MiOrders {
	
	$itemsWrapper: JQuery;
	isAtumOrder: boolean;
	dataOrderItemName: string;
	$orderNotes: JQuery;
	wcAdminMetaBoxes: any = window['woocommerce_admin_meta_boxes']; // WC's global variable.
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.

	constructor(
		private settings: Settings,
		private tooltip: Tooltip
	) {

		this.$itemsWrapper = $( '#woocommerce-order-items, #atum_order_items' );
		this.isAtumOrder = 'atum_order_items' === this.$itemsWrapper.attr( 'id' );
		this.formatMiStuff();

		if ( this.isAtumOrder ) {
			this.dataOrderItemName = 'atum_order_item_id';
			this.$orderNotes = $( '#atum_order_notes' );
		}
		else {
			this.dataOrderItemName = 'order_item_id';
			this.$orderNotes = $( '#woocommerce-order-notes' );
		}

		this.bindEvents();
		this.addHooks();

		// Add mi controls after loading lines via Ajax.
		/** @deprecated: Only for WC 3.47 and older */
		$( document ).ajaxComplete( ( event: JQueryEventObject, xhr: XMLHttpRequest, settings: any ) => {

			if ( settings.data !== undefined ) {

				// Convert the data to an object.
				const data: any = JSON.parse( `{"${ decodeURI( settings.data ).replace( /"/g, '\\"' ).replace( /&/g, '","' ).replace( /=/g, '":"' ) }"}` );

				if ( data.action.indexOf( 'woocommerce' ) !== -1 || data.action.indexOf( 'atum_order' ) !== -1 ) {

					this.formatMiStuff();

					if ( 'woocommerce_add_order_item' === data.action || 'atum_order_add_item' === data.action ) {

						this.$itemsWrapper.find( '.order-item-mi-panel' ).each( ( index: number, elem: Element ) => {

							const $panel: JQuery = $( elem );

							// Only for wc_orders.
							if ( $panel.hasClass( 'multi-price' ) && ! this.isAtumOrder ) {
								this.calcProductMiTotals( $panel, true );
							}

						} );

					}

				}

			}
		} );


		// Add this component to the global scope so can be accessed by other add-ons.
		if ( ! window.hasOwnProperty( 'atum' ) ) {
			window[ 'atum' ] = {};
		}

		window[ 'atum' ][ 'MiOrders' ] = this;
	
	}

	/**
	 * Bind events
	 */
	bindEvents() {
		
		this.$itemsWrapper
			
			// Expand/Collapse the MI UI when clicking the MI icon.
			.on( 'click', 'tr.item .atmi-multi-inventory', ( evt: JQueryEventObject ) => this.toggleInventoryUI( $( evt.currentTarget ).closest( 'tr' ) ) )
			
			// Expand/Collapse the inventories' info.
			.on( 'click', '.toggle-indicator', ( evt: JQueryEventObject ) => {

				evt.preventDefault();

				const $inventory: JQuery = $( evt.currentTarget ).closest( '.order-item-inventory' );
				$inventory.find( '.inventory-info' ).slideToggle( 400, () => $inventory.toggleClass( 'collapsed' ) );

			} )
			
			// Enable edit line inventory fields.
			.on( 'click', '.edit-order-item, .edit-atum-order-item', ( evt: JQueryEventObject ) => {

				evt.preventDefault();

				let $orderItem: JQuery = $( evt.currentTarget ).closest( '.item.with-mi' ),
				    $miPanel: JQuery;

				if ( $orderItem.length ) {
					$miPanel = $orderItem.next();
					this.enableInventoryEdit( $miPanel );
					this.calcProductMiTotals( $miPanel );
				}

			} )
			
			// Do refund lines stuff.
			.on( 'click', '.refund-items', () => this.refundItems() )
			
			// Delete an Inventory
			.on( 'click', '.delete-line', ( evt: JQueryEventObject ) => this.deleteInventory( $( evt.currentTarget ).closest( '.order-item-inventory' ) ) )

			// Set Inventory Purchase price.
			.on( 'click', '.set-mi-purchase-price', ( evt: JQueryEventObject ) => this.setInventoryPurchasePrice( $( evt.currentTarget ).closest( '.inventory-header' ) ) )
			
			// Add Inventories.
			.on( 'click', '.add-inventory', ( evt: JQueryEventObject ) => {

				evt.preventDefault();

				const $inventoryPanel: JQuery = $( evt.currentTarget ).closest( 'tr' );
				let assignedQty = 0;

				$inventoryPanel.find( '.order-item-inventory input.quantity' ).each( function () {
					assignedQty += this.value;
				} );
				this.loadManagementUI( $inventoryPanel.data( this.dataOrderItemName ), $inventoryPanel.prev().find( 'input.quantity ' ).val() - assignedQty );
			} )
			
			// Handle refunds.
			.on( 'click', 'button.mi-do-api-refund, button.mi-do-manual-refund', ( evt: JQueryEventObject ) => this.doManualRefund( $( evt.currentTarget ) ) )
			
			// After table sort. See stupidtable.js.
			.on( 'aftertablesort', '.woocommerce_order_items, .atum_order_items', ( evt: Event, data: any ) => {

				const $table: JQuery = $( evt.currentTarget );

				// Reposition the MI rows after sorting.
				$table.find( 'tr.order-item-mi-panel' ).each( ( index: number, elem: Element ) => {
					$( elem ).insertAfter( $table.find( 'tr.item.with-mi' ).filter( `[data-${ this.dataOrderItemName }="${ $( elem ).data( this.dataOrderItemName ) }"]` ) );
				} );

			} );


		$( 'body' )
		
			// MI inputs quantities changes.
			.on( 'change', '.order-item-mi-panel input.oi_inventory_qty, .order-item-mi-panel .oi_inventory_subtotal, .order-item-mi-panel .oi_inventory_total', ( evt: JQueryEventObject ) => this.inventoryQuantitiesChanged( $( evt.currentTarget ) ) )
			
			// MI inputs REFUND quantities changes.
			.on( 'change', '.order-item-mi-panel input.oi_inventory_refund_qty, .order-item-mi-panel .oi_inventory_refund_total', ( evt: JQueryEventObject ) => this.inventoryRefundQuantitiesChanged( $( evt.currentTarget ) ) )
			
			// Check/uncheck all inventories.
			.on( 'click', '.mi-management th :checkbox', ( evt: JQueryEventObject ) => {

				const $checkbox: JQuery    = $( evt.currentTarget ),
				      $visibleRows: JQuery = $checkbox.closest( 'table' ).find( 'tbody tr:visible:not(.invalid)' ),
				      $swalConfirm: JQuery = $( '.mi-management' ).find( '.swal2-confirm' );

				if ( $checkbox.is( ':checked' ) ) {
					$visibleRows.addClass( 'active' ).find( 'td :checkbox:not(:disabled)' ).prop( 'checked', true );
					$swalConfirm.prop( 'disabled', false );
				}
				else {
					$visibleRows.removeClass( 'active' ).find( 'td :checkbox:not(:disabled)' ).prop( 'checked', false );
					$swalConfirm.prop( 'disabled', true );
				}

			} )
			
			// Check/uncheck single inventory.
			.on( 'click', '.mi-management td :checkbox', ( evt: JQueryEventObject ) => {

				const $checkbox: JQuery    = $( evt.currentTarget ),
				      $table: JQuery       = $checkbox.closest( 'table' ),
				      countChecked: number = $table.find( 'tr:visible td :checkbox:checked' ).length;

				if ( $checkbox.is( ':checked' ) ) {
					$checkbox.closest( 'tr' ).addClass( 'active' );
				}
				else {
					$checkbox.closest( 'tr' ).removeClass( 'active' );
				}

				$( '.mi-management' ).find( '.swal2-confirm' ).prop( 'disabled', ! countChecked );

				if ( $table.find( 'tr:visible td :checkbox' ).length === countChecked ) {
					$table.find( 'thead tr' ).addClass( 'active' ).find( ':checkbox' ).prop( 'checked', true );
				}
				else {
					$table.find( 'thead tr' ).removeClass( 'active' ).find( ':checkbox' ).prop( 'checked', false );
				}

			} )
			
			// Disable quantity fields for MI products on "Add Product" popup in WC 3.5+.
			.on( 'change', '.wc-product-search', ( evt: JQueryEventObject ) => {

				const $select: JQuery = $( evt.currentTarget );

				// Ensure that is the WC 3.5 UI.
				if ( typeof $select.attr( 'multiple' ) !== 'undefined' ) {
					return false;
				}

				const $qtyField: JQuery    = $select.closest( 'tr' ).find( 'input.quantity' ),
				      productTitle: string = Utils.htmlDecode( $select.find( 'option:selected' ).html() );

				if ( ! this.settings.get('createEmptyOrderItems') && productTitle.indexOf( 'mi-product' ) > -1 && ! $qtyField.siblings( '.atmi-multi-inventory' ).length ) {
					$qtyField.prop( 'disabled', true );
					$qtyField.before( `<i class="atum-icon atmi-multi-inventory" title="${ this.settings.get( 'miEnabled' ) }"> ` );

					// The WC Product bundles plugin is encoding the returning titles, so we have to ensure that is being rendered correctly.
					setTimeout( () => $select.siblings( '.select2-container' ).find( '.select2-selection__rendered' ).text( $( productTitle ).text().trim() ), 10 );
				}
				else {
					$qtyField.prop( 'disabled', false );
					$qtyField.siblings( '.atmi-multi-inventory' ).remove();
				}

			} );


		// For POs only.
		if ( 'atum_purchase_order' === this.settings.get( 'orderType' ) ) {

			$( '#po_status' ).on( 'select2:selecting', ( evt: any ) => {

				const $select: JQuery  = $( evt.target );

				// Before changing the status to "received", check if all the items with MI enabled have MI order items.
				if (
					evt.hasOwnProperty( 'params' ) && evt.params.hasOwnProperty( 'args' ) &&
					evt.params.args.hasOwnProperty( 'data' ) && evt.params.args.data.id === this.settings.get( 'completedStatus' )
				) {

					if ( ! this.statusChangeAllowed() ) {

						evt.preventDefault();
						( <any> $select ).select2( 'close' );

						Swal.fire( {
							title            : this.settings.get( 'statusNotUpdated' ),
							icon             : 'warning',
							text             : this.settings.get( 'missingMiOrderItems' ),
							confirmButtonText: this.settings.get( 'ok' ),
						} );

					}
				}

			} );

		}
		// FOR WC Orders only.
		else if( 'shop_order' === this.settings.get( 'orderType' ) ) {

			$( '#order_status' ).on( 'select2:selecting', ( evt: any ) => {

				const $select: JQuery = $( evt.target ),
				      originalStatus: String  = $select.val();

				// Before changing the status to one with "stock deducted",check if all the items with MI enabled have MI order items.
				if (
					-1 === this.settings.get( 'reduceStockStatuses' ).indexOf( originalStatus ) && evt.hasOwnProperty( 'params' ) &&
					evt.params.hasOwnProperty( 'args' ) && evt.params.args.hasOwnProperty( 'data' ) &&
					0 <= this.settings.get( 'reduceStockStatuses' ).indexOf( evt.params.args.data.id )
				) {



					if ( ! this.statusChangeAllowed() ) {

						evt.preventDefault();
						( <any> $select ).select2( 'close' );

						Swal.fire( {
							title            : this.settings.get( 'statusNotUpdated' ),
							icon             : 'warning',
							text             : this.settings.get( 'missingMiOrderItems' ),
							confirmButtonText: this.settings.get( 'continue' ),
							showCancelButton : true,
							cancelButtonText : this.settings.get( 'cancel' ),
						} ).then( ( result: SweetAlertResult ) => {

							if ( result.isConfirmed ) {
								$select.val( evt.params.args.data.id ).change();
							}
						} );

					}
				}

			} );

		}
	
	}

	/**
	 * Add JS hooks
	 */
	addHooks() {

		// Delete related MI order item inventories after deleting an atum order line.
		this.wpHooks.addAction( 'orderItems_deleteItem_removed', 'atum', ( $container: JQuery, itemId: number ) => {
			$container.find( `#atum_order_line_items tr[data-atum_order_item_id=${ itemId }]` ).remove();
		} );

		// Change the stock in bulk using the Increase/Decrease stock buttons.
		// NOTE: This only applies to ILs now.
		this.wpHooks.addFilter( 'ordersBulkActions_bulkChangeStock', 'atum', ( maybeProcessItems: boolean, $rows: JQuery, action: string, resolve: Function ) => {

			const bulkChangeResult: any = this.bulkChangeStock( action, $rows, resolve );

			return false;  // Must return false to bypass the default behaviour,

		} );

	}
	
	/**
	 * Change MI products order lines style and behavior
	 */
	formatMiStuff () {

		const $miEnabledRows: JQuery = this.$itemsWrapper.find( '.with-mi' ),
		      isPO: boolean          = 'atum_purchase_order' === this.settings.get( 'orderType' );

		$miEnabledRows.each( ( index: number, elem: Element ) => {

			const $row: JQuery            = $( elem ),
			      $inventoryPanel: JQuery = $row.next( '.order-item-mi-panel' ),
				  hasInventories: boolean = 0 < $inventoryPanel.find( '.order-item-inventory' ).length,
				  readOnly: boolean       = ! isPO && ( ! this.settings.get( 'createEmptyOrderItems' ) || hasInventories )
				      || ( isPO && hasInventories );

			let nextWidth: number = 0;

			// If it's a PO, we mustn't block the fields if there is no MI item added because they can be added later.
			$row.find( '.line_cost .split-input input' ).prop( 'readonly', readOnly );
			$row.find( '.line_cost input.refund_line_total' ).prop( 'readonly', readOnly );
			$row.find( '.quantity input[type=number]' ).prop( 'readonly', readOnly );


			$row.find( '.line_cost' ).nextAll().each( ( index: number, elem: Element ) => {
				nextWidth += $( elem ).outerWidth();
			} );

			$inventoryPanel.find( '.atum-line-actions' ).width( nextWidth - 26 - 26 ); // 26, .line-tax and .wc-order-edit-line-item padding.
			this.$itemsWrapper.find( '.do-manual-refund' ).removeClass( 'do-manual-refund' ).addClass( 'mi-do-manual-refund' );
			this.$itemsWrapper.find( '.do-api-refund' ).removeClass( 'do-api-refund' ).addClass( 'mi-do-api-refund' );

			// Avoid to remove the buttons. Remove later if not needed.
			//this.$itemsWrapper.find( '.bulk-decrease-stock, .bulk-increase-stock' ).remove();

		} );
		
	}
	
	/**
	 * Load the MI Inventory Selector
	 *
	 * @param {number} orderItemId
	 * @param {number} minQty
	 */
	loadManagementUI( orderItemId: number, minQty: number ) {

		let $miManagementModal: JQuery;
		const $template: JQuery = $( `#product-inventories-${ orderItemId }` );

		Swal.fire( {
			title             : this.settings.get( 'managementPopupTitle' ),
			html              : $template.html(),
			showCancelButton  : false,
			showCloseButton   : true,
			confirmButtonText : `<i class="atum-icon atmi-plus-circle"></i>${ this.settings.get( 'saveButton' ) }`,
			confirmButtonColor: '#00B8DB',
			customClass       : {
				container: 'mi-management',
			},
			allowOutsideClick : false,
			didOpen           : () => {

				$miManagementModal = $( '.mi-management' );

				// Disable the button until a change is made.
				$miManagementModal.find( '.swal2-confirm' ).prop( 'disabled', true );

				// Add the tooltips to modal (if any).
				this.tooltip.addTooltips( $miManagementModal );

				// Hide all the inventory rows that were already added to the order item.
				$miManagementModal.find( 'tbody tr' ).show().each( ( index: number, elem: Element ) => {

					const $row: JQuery        = $( elem ),
					      inventoryId: number = $row.data( 'inventory_id' );

					if ( this.wpHooks.applyFilters( 'miOrders_loadManagementUI_orderItemInventory', this.$itemsWrapper.find( `tr.order-item-mi-panel[data-${ this.dataOrderItemName }="${ orderItemId }"] .order-item-inventory[data-inventory_id=${ inventoryId }]` ), orderItemId, inventoryId ).length ) {
						$row.hide();
					}

				} );

			},
		} )
		.then( ( result: SweetAlertResult ) => {
			
			if ( result.isConfirmed ) {

				const html: string                = $( `#new-order-item-inventory` ).html().replace( /\{\{item_id\}\}/gi, orderItemId.toString() ),
				      $miPanel: JQuery            = this.$itemsWrapper.find( `tr.order-item-mi-panel[data-${ this.dataOrderItemName }="${ orderItemId }"]` ),
				      $inventoriesWrapper: JQuery = $miPanel.find('.order-item-mi-wrapper'),
				      $chkVisibles: JQuery        = $miManagementModal.find( 'tbody :checkbox:visible' ),
				      $chkChecked: JQuery         = $chkVisibles.filter( ':checked' );

				let $firstChecked: JQuery,
					firstCost: number = 0,
					firstDiscounted: number = 0;
				
				$chkChecked.each( ( index: number, elem: Element ) => {

					// Allow adding the inventory externally.
					if ( this.wpHooks.applyFilters( 'miOrders_loadManagementUI_maybeAddInventory', true, elem, html, orderItemId ) ) {

						const $popupInventoryLine: JQuery = $( elem ).closest( 'tr' ),
						      inventoryId: number         = $popupInventoryLine.data( 'inventory_id' ),
						      isMain: boolean             = $popupInventoryLine.data( 'is_main' ),
						      $newInventory: JQuery       = $( html.replace( /\{\{inventory_id\}\}/gi, inventoryId.toString() ) ),
						      $infoFields: JQuery         = $newInventory.find( '.info-fields' );

						let cost: number,
						    discounted: number,
						    qty: number;

						// Add the inventory to the order.
						$newInventory.insertBefore( $inventoriesWrapper.find('.after-item-inventory'));

						$newInventory.attr( 'data-inventory_id', inventoryId ).attr( 'data-is_main', isMain ? 'true' : 'false' )
							.find( '.inventory-name .mi-text' ).prepend( $popupInventoryLine.find( '.name span' ).text().trim() );

						$newInventory.find( '.stock_available' ).text( $popupInventoryLine.find( '.stock-available' ).text().trim() );
						$newInventory.find( '.line_cost .view, .item_cost .view' ).html( $popupInventoryLine.find( '.cost' ).html().replace( '.', this.settings.get( 'decimalSeparator' ) ) );

						qty = $newInventory.find( '.oi_inventory_qty' ).data( 'qty' );

						// <string>Utils.formatNumber( $inventoryLine.find( '.cost' ).data( 'price' ) * qty, this.settings.get( 'roundingPrecision' ), '', this.settings.get( 'decimals' ) )

						minQty -= qty;
						cost = $popupInventoryLine.find( '.cost' ).data( 'price' ) * qty;
						discounted = $popupInventoryLine.find( '.discounted' ).data( 'price' ) * qty;

						if ( typeof $firstChecked === 'undefined' ) {
							$firstChecked = $newInventory;
							firstCost = cost;
							firstDiscounted = discounted;
						}

						$newInventory.find( '.oi_inventory_subtotal' ).attr( 'data-subtotal', cost ).val( cost.toString().replace( '.', this.settings.get( 'decimalSeparator' ) ) );
						$newInventory.find( '.oi_inventory_total' ).attr( 'data-total', discounted ).val( discounted.toString().replace( '.', this.settings.get( 'decimalSeparator' ) ) );

						// Add the inventory info fields.
						$infoFields.find( '.region' ).text( $popupInventoryLine.find( 'input[name=region]' ).val() );
						$infoFields.find( '.location' ).text( $popupInventoryLine.find( 'input[name=location]' ).val() );
						$infoFields.find( '.inventory-date' ).text( $popupInventoryLine.find( 'input[name=inventory-date]' ).val() );
						$infoFields.find( '.bbe-date' ).text( $popupInventoryLine.find( 'input[name=bbe-date]' ).val() );
						$infoFields.find( '.lot' ).text( $popupInventoryLine.find( 'input[name=lot]' ).val() );

						if ( isMain ) {
							$miPanel.next('.order-item-bom-tree-panel').hide();
						}

						this.enableInventoryEdit( $miPanel );
						this.calcProductMiTotals( $miPanel );

					}

				} );

				// There are remaining qtys to assign.
				if ( 0 < minQty && typeof $firstChecked !== 'undefined' ) {

					let cost: number,
					    discounted: number;

					minQty++;

					$firstChecked.find( '.oi_inventory_qty' ).data( 'qty', minQty ).val( minQty.toString().replace( '.', this.settings.get( 'decimalSeparator' ) ));

					cost = firstCost * minQty;
					discounted = firstDiscounted * minQty;

					$firstChecked.find( '.oi_inventory_subtotal' ).attr( 'data-subtotal', cost ).val( cost.toString().replace( '.', this.settings.get( 'decimalSeparator' ) ) );
					$firstChecked.find( '.oi_inventory_total' ).attr( 'data-total', discounted ).val( discounted.toString().replace( '.', this.settings.get( 'decimalSeparator' ) ) );

					this.enableInventoryEdit( $miPanel );
					this.calcProductMiTotals( $miPanel );
				}

				if ( $chkChecked.length === $chkVisibles.length ) {
					// All the inventories are selected.
					$inventoriesWrapper.find( '.add-inventory' ).hide();
				}

				// Make sure the item row is unmarked as 'invalid'.
				if ( $chkChecked.length ) {
					$miPanel.prev( `tr.item[data-${ this.dataOrderItemName }="${ orderItemId }"]` ).removeClass( 'invalid' );
					this.formatMiStuff();
					this.tooltip.addTooltips( $inventoriesWrapper );
				}
				
			}
			
		});
		
	}
	
	/**
	 * Enable inventory edition fields
	 *
	 * @param {JQuery} $miPanel
	 */
	enableInventoryEdit( $miPanel: JQuery ) {

		const hasReducedStock = true === $miPanel.data('reducedStock');

		let $orderItemInventories: JQuery = $miPanel.find( '.order-item-inventory' );

		$miPanel.addClass( 'editing' ).find( '.order-item-mi-wrapper' ).show();

		// Do global actions for all mi items.
		$orderItemInventories.find( '.view' ).hide();
		$orderItemInventories.find( '.atum-line-actions' ).width( '' );
		$orderItemInventories.removeClass( 'collapsed' ).addClass( 'editing' );
		$miPanel.prev().addClass( 'editing' );

		if ( hasReducedStock && 1 === $orderItemInventories.length ) {
			$orderItemInventories.find( '.delete-line' ).hide();
		}
		else {
			$orderItemInventories.find( '.delete-line' ).show();
		}
		// include the after-item-inventory element.
		$miPanel.find( '.edit' ).show();

		
	}
	
	/**
	 * Expand/Collapse the inventory UI
	 *
	 * @param {JQuery} $itemRow
	 */
	toggleInventoryUI( $itemRow: JQuery ) {

		const $nextRow: JQuery = $itemRow.next();

		if ( $nextRow.hasClass( 'order-item-mi-panel' ) && ! $nextRow.hasClass( 'editing' ) ) {

			const $miWrapper: JQuery = $nextRow.find( '.order-item-mi-wrapper' );

			if ( $miWrapper.find( '.order-item-inventory' ).length ) {
				$miWrapper.slideToggle();
			}
		}

	}
	
	/**
	 * Set the item row's total values calculated from the MultiInventory panel
	 *
	 * @param {JQuery}  $miPanel
	 * @param {boolean} chgViewFields
	 */
	calcProductMiTotals( $miPanel: JQuery, chgViewFields: boolean = false ) {

		const $miLineItem: JQuery = $miPanel.prev(),
		      $miRows: JQuery     = $miPanel.find( '.order-item-inventory' );

		if ( ! $miRows.length ) {
			return;
		}

		let qty: number          = 0,
		    subTotal: number     = 0,
		    total: number        = 0,
		    viewLineDisc: string = '',
		    viewItemDisc: string = '';

		$miRows.each( ( index: number, elem: Element ) => {

			const $row: JQuery = $( elem );

			qty += parseFloat( $row.find( '.oi_inventory_qty' ).val() );
			subTotal += <number> Utils.unformat( $row.find( 'input.oi_inventory_subtotal' ).val(), this.settings.get( 'decimalSeparator' ) );
			total += <number> Utils.unformat( $row.find( 'input.oi_inventory_total' ).val(), this.settings.get( 'decimalSeparator' ) );
		} );

		const strSubTotal: string = subTotal.toString().replace( '.', this.settings.get( 'decimalSeparator' ) );

		const strTotal: string = total.toString().replace( '.', this.settings.get( 'decimalSeparator' ) );

		$miLineItem.find( 'input.quantity' ).val( qty ).data( 'qty', qty ).change();
		$miLineItem.find( 'input.line_subtotal' ).val( strSubTotal ).data( 'subtotal', strSubTotal );
		$miLineItem.find( 'input.line_total' ).val( strTotal ).data( 'total', strTotal );

		if ( chgViewFields ) {

			const symbol: string    = this.wcAdminMetaBoxes.currency_format_symbol,
			      precision: number = this.wcAdminMetaBoxes.currency_format_num_decimals,
			      thousand: string  = this.wcAdminMetaBoxes.currency_format_thousand_sep,
			      decimal: string   = this.wcAdminMetaBoxes.currency_format_decimal_sep,
			      format: string    = this.wcAdminMetaBoxes.currency_format;

			if ( 0 !== ( subTotal - total ) ) {
				viewLineDisc = <string> Utils.formatMoney( subTotal - total, symbol, precision, thousand, decimal, format );
				viewItemDisc = <string> Utils.formatMoney( ( subTotal - total / qty ), symbol, precision, thousand, decimal, format );
			}

			$miLineItem.find( '.line_cost .view > .amount' ).html( <string> Utils.formatMoney( subTotal, symbol, precision, thousand, decimal, format ) );
			$miLineItem.find( '.line_cost .view .wc-order-item-discount .amount' ).html( viewLineDisc );
			$miLineItem.find( '.item_cost .view > .amount' ).html( <string> Utils.formatMoney( subTotal / qty, symbol, precision, thousand, decimal, format ) );
			$miLineItem.find( '.item_cost .view .wc-order-item-discount .amount' ).html( viewItemDisc );

		}
		
	}

	/**
	 * Delete an inventory
	 *
	 * @param {JQuery} $itemInventory
	 */
	deleteInventory( $itemInventory: JQuery ) {

		const $miPanel: JQuery = $itemInventory.closest( '.order-item-mi-panel' );

		this.tooltip.destroyTooltips( $itemInventory );
		$itemInventory.remove();
		this.calcProductMiTotals( $miPanel );
		this.enableInventoryEdit( $miPanel );

		if ( 0 === $miPanel.find( '.order-item-inventory' ).length ) {
			this.formatMiStuff();
		}

		if ( $itemInventory.data( 'is_main' ) ) {

			$miPanel.next('.order-item-bom-tree-panel').show();
		}

		// At least one inventory is selected.
		$miPanel.find( '.add-inventory' ).show();
		
	}

	/**
	 * Set the purchase price for an inventory order item
	 *
	 * @param {JQuery} $itemInventoryHeader
	 */
	setInventoryPurchasePrice( $itemInventoryHeader: JQuery ) {

		const qty: number           = parseFloat( $itemInventoryHeader.find( '.quantity .edit input' ).val() ),
		      $lineSubTotal: JQuery = $itemInventoryHeader.find( 'input.line_subtotal' ),
		      $lineTotal: JQuery    = $itemInventoryHeader.find( 'input.line_total' ),
		      purchasePrice: number = qty !== 0 ? <number> Utils.unformat( $lineTotal.val() || 0, this.settings.get( 'decimalSeparator' ) ) / qty : 0,
		      data: any             = {
			      inventory_id                                 : $itemInventoryHeader.parent().data( 'inventory_id' ),
			      action                                       : 'atum_mi_change_purchase_price',
			      token                                        : this.settings.get( 'nonce' ),
			      [ this.settings.get( 'purchasePriceField' ) ]: purchasePrice,
		      };
		
		Swal.fire({
			html               : this.settings.get('confirmPurchasePrice').replace('{{number}}', `<strong>${ purchasePrice }</strong>`),
			icon               : 'question',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get('continue'),
			cancelButtonText   : this.settings.get('cancel'),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {

				return new Promise( ( resolve: Function, reject: Function ) => {

					$.ajax( {
						url     : window[ 'ajaxurl' ],
						data    : data,
						type    : 'POST',
						dataType: 'json',
						success : ( response: any ) => {

							if ( response.success === false ) {
								Swal.showValidationMessage( response.data );
							}

							resolve();

						},
					});
					
				});
				
			}
		})
		.then( ( result: SweetAlertResult ) => {

			if ( result.isConfirmed ) {

				$lineTotal.data( 'total', Utils.unformat( $lineTotal.val(), this.settings.get( 'decimalSeparator' ) ) );
				$lineSubTotal.val( $lineTotal.val() ).data( 'subtotal', $lineTotal.data( 'total' ) ).change();

				Swal.fire( {
					title            : this.settings.get( 'done' ),
					text             : this.settings.get( 'purchasePriceChanged' ),
					icon             : 'success',
					confirmButtonText: this.settings.get( 'ok' ),
				} );

			}
			
		});
	
	}

	/**
	 * Increase/Decrease stock
	 * NOTE: only applies to ILs now.
	 *
	 * @param {string}   action  The action to perform: 'increase' or 'decrease'.
	 * @param {JQuery}   $rows   The selected order item rows.
	 * @param {Function} resolve As this is being executed during the sweetalert promise, is expecting to be resloved.
	 */
	bulkChangeStock( action: string, $rows: JQuery, resolve: Function ) {

		let quantities: any   = {},
		    miQuantities: any = {},
		    itemIds: number[] = [],
		    noteSecurity: string,
		    noteAction: string,
		    tableClass: string,
		    totalQty: number  = 0;

		this.$itemsWrapper.find( '.item.selected .quantity input.quantity' ).each( ( index: number, elem: Element ) => {
			totalQty += parseFloat( $( elem ).val() );
		} );
		
		if ( totalQty === 0 ) {
			
			// No stock to increase/reduce in the order. Just show the error on the already displayed modal.
			Swal.showValidationMessage( this.settings.get( 'noQty' ) );
			resolve();
			
		}
		else {

			if ( this.isAtumOrder ) {
				tableClass = 'atum_order_items';
				noteAction = 'atum_order_add_note';
				noteSecurity = window[ 'atumOrder' ].add_note_nonce;
			}
			else {
				tableClass = 'woocommerce_order_items';
				noteAction = 'woocommerce_add_order_note';
				noteSecurity = this.wcAdminMetaBoxes.add_order_note_nonce;
			}

			const $table: JQuery  = $( `table.${ tableClass }` ),
			      $miRows: JQuery = $table.find( '.order-item-mi-panel' );

			$rows.each( ( index: number, elem: Element ) => {

				const $row: JQuery = $( elem );

				if ( $row.find( 'input.quantity' ).length ) {
					quantities[ $row.attr( `data-${ this.dataOrderItemName }` ) ] = $row.find( 'input.quantity' ).val();
				}

				itemIds.push( parseInt( $row.data( this.dataOrderItemName ), 10 ) );

			} );

			$miRows.each( ( index: number, elem: Element ) => {

				const $miRow: JQuery      = $( elem ),
				      orderItemId: string = $miRow.attr( `data-${ this.dataOrderItemName }` );

				if ( $miRow.find( 'input.quantity' ).length ) {

					miQuantities[ orderItemId ] = {};
					$miRow.find( '.order-item-inventory' ).each( ( index: number, elem: Element ) => {
						const $inv = $( elem );
						miQuantities[ orderItemId ][ $inv.attr( 'data-inventory_id' ) ] = $inv.find( 'input.quantity' ).val();
					} );

				}

			} );

			$.ajax( {
				url     : window[ 'ajaxurl' ],
				data    : {
					action            : 'atum_mi_change_stock',
					token             : this.settings.get( 'nonce' ),
					operation         : action,
					order_id          : $( '#post_ID' ).val(),
					order_item_ids    : itemIds,
					order_item_qty    : quantities,
					order_item_inv_qty: miQuantities,
				},
				method  : 'POST',
				dataType: 'json',
				success : ( response: any ) => {

					if ( true === response.success ) {

						$.map( response.data, ( item: any ) => {

							// No items were updated.
							if ( ! item.success ) {
								window.alert( item.note );
								return;
							}

							const orderNoteData: any = {
								action   : noteAction,
								post_id  : $( '#post_ID' ).val(),
								note     : item.note,
								note_type: '',
								security : noteSecurity,
							};

							$.post( window[ 'ajaxurl' ], orderNoteData, ( response: any ) => {
								this.$orderNotes.find( '.order_notes, .atum_order_notes' ).prepend( response );
							} );
						} );

					}
					else {
						Swal.showValidationMessage( response.data );
					}

					resolve();

				},
			} );
			
		}
		
	}

	/**
	 * Refund items with MI
	 */
	refundItems() {

		const $orderItem: JQuery = this.$itemsWrapper.find( '.item.with-mi' );

		if ( $orderItem.length ) {
			const $inventory = $orderItem.next();
			$inventory.addClass( 'editing' ).find( '.order-item-mi-wrapper' ).show();
		}
		
	}

	/**
	 * Manual refunds
	 *
	 * @param {JQuery} $button
	 */
	doManualRefund( $button: JQuery ) {

		Blocker.block( this.$itemsWrapper, {
			background: '#FFF',
			opacity   : 0.6,
		} );
		
		if ( window.confirm( this.wcAdminMetaBoxes.i18n_do_refund ) ) {

			const refundAmount: number   = $( 'input#refund_amount' ).val(),
			      refundReason: string   = $( 'input#refund_reason' ).val(),
			      refundedAmount: number = $( 'input#refunded_amount' ).val();
			
			// Get line item refunds
			let lineItemQtys: any      = {},
			    lineItemTotals: any    = {},
			    lineItemTaxTotals: any = {};

			$( '.refund input.refund_order_item_qty' ).each( ( index: number, elem: Element ) => {

				const $refundInput: JQuery = $( elem );

				if ( $refundInput.closest( 'tr' ).data( 'order_item_id' ) ) {
					if ( $refundInput.val() ) {
						lineItemQtys[ $refundInput.closest( 'tr' ).data( 'order_item_id' ) ] = $refundInput.val();
					}
				}

			} );

			$( '.refund input.refund_line_total' ).each( ( index: number, elem: Element ) => {

				const $refundInput: JQuery = $( elem );

				if ( $refundInput.closest( 'tr' ).data( 'order_item_id' ) ) {
					lineItemTotals[ $refundInput.closest( 'tr' ).data( 'order_item_id' ) ] = Utils.unformat( $refundInput.val(), this.settings.get( 'decimalSeparator' ) );
				}

			} );

			$( '.refund input.refund_line_tax' ).each( ( index: number, elem: Element ) => {

				const $refundInput: JQuery = $( elem );

				if ( $refundInput.closest( 'tr' ).data( 'order_item_id' ) ) {

					if ( ! lineItemTaxTotals[ $refundInput.closest( 'tr' ).data( 'order_item_id' ) ] ) {
						lineItemTaxTotals[ $refundInput.closest( 'tr' ).data( 'order_item_id' ) ] = {};
					}

					lineItemTaxTotals[ $refundInput.closest( 'tr' ).data( 'order_item_id' ) ][ $refundInput.data( 'tax_id' ) ] = Utils.unformat( $refundInput.val(), this.settings.get( 'decimalSeparator' ) );
				}

			} );

			const data: any = {
				action                : 'woocommerce_refund_line_items',
				order_id              : $( '#post_ID' ).val(),
				refund_amount         : refundAmount,
				refunded_amount       : refundedAmount,
				refund_reason         : refundReason,
				refund_inventory      : $( '.oi_inventory_refund_qty, .oi_inventory_refund_total' ).serialize(),
				line_item_qtys        : JSON.stringify( lineItemQtys, null, '' ),
				line_item_totals      : JSON.stringify( lineItemTotals, null, '' ),
				line_item_tax_totals  : JSON.stringify( lineItemTaxTotals, null, '' ),
				api_refund            : $button.is( '.mi-do-api-refund' ) ? 'true' : 'false',
				restock_refunded_items: $( '#restock_refunded_items:checked' ).length ? 'true' : 'false',
				security              : this.wcAdminMetaBoxes.order_item_nonce,
			};

			$.post( this.wcAdminMetaBoxes.ajax_url, data, ( response: any ) => {

				if ( true === response.success ) {
					// Redirect to same page for show the refunded status.
					window.location.href = window.location.href;
				}
				else {
					window.alert( response.data.error );
					window[ 'wc_meta_boxes_order' ].reload_items();
					Blocker.unblock( this.$itemsWrapper );
				}

			} );
			
		}
		else {
			Blocker.unblock( this.$itemsWrapper );
		}
		
	}
	
	/**
	 * Refresh the line item quantities from the inventories changes
	 *
	 * @param {JQuery} $input
	 */
	inventoryQuantitiesChanged( $input: JQuery ) {

		const $row: JQuery        = $input.closest( '.order-item-inventory' ),
		      $iqSubtotal: JQuery = $row.find( 'input.oi_inventory_subtotal' ),
		      $iqTotal: JQuery    = $row.find( 'input.oi_inventory_total' );

		if ( $input.hasClass( 'oi_inventory_qty' ) ) {

			const qty: number     = parseFloat( $input.val() || 0 ),
			      origQty: number = parseFloat( $input.data( 'qty' ) || 0 );
			
			// SubTotals.
			const unitSubtotal: number = origQty !== 0 ? <number> Utils.unformat( $iqSubtotal.data( 'subtotal' ), this.settings.get( 'decimalSeparator' ) ) / origQty || 0 : 0;

			$iqSubtotal.val(
				parseFloat( <string> Utils.formatNumber( unitSubtotal * qty, this.settings.get( 'roundingPrecision' ), '' ) )
					.toString()
					.replace( '.', this.settings.get( 'decimalSeparator' ) ),
			);

			const unitDiscount: number = origQty !== 0 ? <number> Utils.unformat( $iqTotal.data( 'total' ), this.settings.get( 'decimalSeparator' ) ) / origQty || 0 : 0;

			$iqTotal.val(
				parseFloat( <string> Utils.formatNumber( unitDiscount * qty, this.settings.get( 'roundingPrecision' ), '' ) )
					.toString()
					.replace( '.', this.settings.get( 'decimalSeparator' ) ),
			);
			
		}

		this.calcProductMiTotals( $row.closest( '.order-item-mi-panel' ) );
		
	}
	
	/**
	 * Refresh the line item refund quantities from the inventories changes
	 *
	 * @param {JQuery} $input
	 */
	inventoryRefundQuantitiesChanged( $input: JQuery ) {

		const $row: JQuery     = $input.closest( '.inventory-header' ),
		      $iqTotal: JQuery = $row.find( 'input.oi_inventory_refund_total' );

		if ( $input.hasClass( 'oi_inventory_refund_qty' ) ) {

			const qty: number       = parseFloat( $input.val() ),
			      unityCost: number = parseFloat( $row.find( '.oi_inventory_total' ).data( 'total' ) || 0 ) / parseFloat( $row.find( '.oi_inventory_qty' ).val() ),
			      total: number     = qty * unityCost;

			// Totals.
			$iqTotal.val(
				parseFloat( <string> Utils.formatNumber( total, this.settings.get( 'decimals' ), '' ) )
					.toString()
					.replace( '.', this.settings.get( 'decimalSeparator' ) ),
			);

		}

		this.calcProductRefundMiTotals( $row.closest( '.order-item-mi-panel' ) );
		
	}
	
	/**
	 * Set the item line product total quantities calculated from the MultiInventory panel
	 *
	 * @param {JQuery} $miPanel
	 */
	calcProductRefundMiTotals( $miPanel: JQuery ) {

		const $miLineItem: JQuery = $miPanel.prev(),
		      $miRows: JQuery     = $miPanel.find( '.order-item-inventory' );

		let qty: number         = 0,
		    totalRefund: number = 0,
		    strTotalRefund: string;

		$miRows.each( ( index: number, elem: Element ) => {
			const $row: JQuery = $( elem );

			qty += parseFloat( $row.find( '.oi_inventory_refund_qty' ).val() || 0 );
			totalRefund += <number> Utils.unformat( $row.find( 'input.oi_inventory_refund_total' ).val() || 0, this.settings.get( 'decimalSeparator' ) );
		} );

		strTotalRefund = parseFloat( <string> Utils.formatNumber( totalRefund, this.settings.get( 'decimals' ), '' ) )
			.toString()
			.replace( '.', this.settings.get( 'decimalSeparator' ) );

		$miLineItem.find( 'input.refund_order_item_qty' ).val( qty ).data( 'qty', qty ).change();
		$miLineItem.find( 'input.refund_line_total' ).val( strTotalRefund ).change();
		
	}
	
	/**
	 * Check that the stock quantities added match the original stock deducted and notify the user
	 *
	 * @param {JQuery} $miManangement
	 */
	checkStockQuantities( $miManangement: JQuery ) {

		const originalDeducted: number = parseFloat( $miManangement.find( '.total-used-stock' ).data( 'used' ) || 0 ),
		      currentDeducted: number  = this.inputSum( $miManangement ),
		      $miMessages: JQuery      = $miManangement.find( '.stock-messages' ),
		      $alert: JQuery           = $( '<div class="alert" />' );

		let message: string = '';

		if ( originalDeducted < currentDeducted ) {
			message = this.settings.get( 'originalLower' ).replace( '{{originalStock}}', `<strong>${ originalDeducted }</strong>` )
				.replace( '{{currentStock}}', `<strong>${ currentDeducted }</strong>` );

			$miMessages.html( $alert.addClass( 'alert-warning' ).append( `<div>${ message }</div>` ).html() );
		}
		else if ( originalDeducted > currentDeducted ) {
			message = this.settings.get( 'originalLower' ).replace( '{{originalStock}}', `<strong>${ originalDeducted }</strong>` )
				.replace( '{{currentStock}}', `<strong>${ currentDeducted }</strong>` );

			$miMessages.html( $alert.addClass( 'alert-warning' ).append( `<div>${ message }</div>` ).html() );
		}
		else {
			$miMessages.html( $alert.addClass( 'alert-success' ).append( `<div>${ this.settings.get( 'matchingStocks' ) }</div>` ).html() );
		}

		$miMessages.slideDown( 'fast' );
		
	}
	
	/**
	 * Sum the quantities inside the numeric inputs in a wrapper element
	 *
	 * @param {JQuery} $wrapper
	 *
	 * @return {number}
	 */
	inputSum( $wrapper: JQuery ) {

		let result: number = 0;

		$wrapper.find( 'input[type=number]' ).each( ( index: number, elem: Element ) => {
			result += parseFloat( $( elem ).val() || 0 );
		} );

		return result;

	}

	/**
	 * Calculate if the status can change when the order has products with MI enabled.
	 *
	 * @return {boolean}
	 */
	statusChangeAllowed() {

		let statusChangeAllowed: boolean = true;

		// Check if all the items with MI enabled, have at least one MI order item assigned.
		this.$itemsWrapper.find( 'tr.with-mi' ).each( ( index: number, elem: Element ) => {

			const $row: JQuery = $( elem );

			if ( ! $row.next( '.order-item-mi-panel' ).find( '.order-item-inventory' ).length ) {
				statusChangeAllowed = false;
				$row.addClass( 'invalid' );
			}

		} );

		return statusChangeAllowed;

	}
	
}