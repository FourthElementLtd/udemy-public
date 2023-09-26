/* =======================================
   MULTI-INVENTORY UI
   ======================================= */

import DateTimePicker from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_date-time-picker';
import EditPopovers from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_edit-popovers';
import EnhancedSelect from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_enhanced-select';
import Settings from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';
import Tooltip from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_tooltip';
import Utils from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/utils/_utils';

export default class MiUI {
	
	$productDataBox: JQuery;
	$inventoryPanel: JQuery;
	$shippingPanel: JQuery;
	$atumPanel: JQuery;
	currentProductType: string;
	isUIPrepared: boolean[] = [ false ];

	constructor(
		private settings: Settings,
		private editPopovers: EditPopovers,
		private dateTimePicker: DateTimePicker,
		private enhancedSelect: EnhancedSelect,
		private tooltip: Tooltip,
	) {
		
		// Initialize selectors
		this.$productDataBox = $( '#woocommerce-product-data' );
		this.$inventoryPanel = this.$productDataBox.find( '#inventory_product_data' );
		this.$shippingPanel = this.$productDataBox.find( '#shipping_product_data' );
		this.$atumPanel = this.$productDataBox.find( '#atum_product_data' );
		this.currentProductType = this.$productDataBox.find( '#product-type option:selected' ).val();
		
		this.bindEvents();
		
	}
	
	bindEvents() {
		
		// Detect product type change.
		this.$productDataBox.find( '#product-type' ).change( ( evt: JQueryEventObject ) => {
			
			this.currentProductType = $( evt.currentTarget ).find( 'option:selected' ).val();
			
			const $multiInventoryChecked: JQuery = this.$atumPanel.find( '.multi_inventory_buttons input:radio:checked' ),
			      $compatibleType: JQuery        = this.settings.get( 'compatibleTypes' ).find( ( type: string ) => {
				      return type === this.currentProductType;
			      } );
			
			if ( typeof $compatibleType === 'undefined' ) {
				this.switchMultiInventory( $multiInventoryChecked, 'no' );
			}
			else {
				this.switchMultiInventory( $multiInventoryChecked );
			}
			
		} ).change();
		
		//
		// Multi-inventory settings at product level.
		// ------------------------------------------
		this.bindMiSettings();
		
		//
		// Multi-Inventory UI events.
		// --------------------------
		this.$productDataBox
			
			// Expand/Collapse inventory blocks.
			.on( 'click', '.inventory-name .toggle-indicator', ( evt: JQueryEventObject ) => {
				evt.preventDefault();
				this.toggleInventory( $( evt.currentTarget ).closest( '.inventory-group' ) );
			} )
			
			// Expand/Collapse all the inventory blocks.
			.on( 'click', '.toggle-all', ( evt: JQueryEventObject ) => {
				
				evt.preventDefault();
				
				let $toggleButton: JQuery = $( evt.currentTarget ),
				    $uiWrapper: JQuery    = $toggleButton.closest( '.multi-inventory-fields' ),
				    curStatus: string     = $toggleButton.data( 'status' ) || 'collapsed';
				
				$uiWrapper.find( '.inventory-group' ).each( ( index: number, elem: Element ) => {
					
					const $inventoryGroup: JQuery = $( elem );
					
					if ( curStatus === 'collapsed' ) {
						$inventoryGroup.find( '.inventory-name' ).removeClass( 'collapsed' )
							.siblings( '.inventory-fields' ).slideDown();
					}
					else {
						$inventoryGroup.find( '.inventory-name' ).addClass( 'collapsed' )
							.siblings( '.inventory-fields' ).slideUp();
					}
					
				} );
				
				$toggleButton.data( 'status', curStatus === 'collapsed' ? 'expanded' : 'collapsed' );
				
			} )
			
			// Add new Inventory button.
			.on( 'click', '.add-inventory', ( evt: JQueryEventObject ) => {
				
				evt.preventDefault();
				
				const $button: JQuery    = $( evt.currentTarget ),
				      $uiWrapper: JQuery = $button.closest( '.multi-inventory-fields' );
				
				Swal.fire( {
					title            : this.settings.get( 'typeName' ),
					input            : 'text',
					showCancelButton : true,
					confirmButtonText: this.settings.get( 'add' ),
					cancelButtonText : this.settings.get( 'cancel' ),
					allowOutsideClick: true,
					inputValidator   : ( value: any ) => {
						
						return new Promise( ( resolve: Function, reject: Function ) => {
							
							if ( ! value ) {
								reject( this.settings.get( 'mustHaveName' ) );
							}
							else {
								
								const $matchingNames = $uiWrapper.find( '.inventory-name > span' ).filter( ( index: number, elem: Element ) => {
									return $( elem ).text().trim().toLowerCase() === value.toLowerCase();
								} );
								
								if ( $matchingNames.length ) {
									reject( this.settings.get( 'nameAlreadyUsed' ) );
								}
								else {
									resolve();
								}
								
							}
							
						} );
						
					},
				} )
				.then( ( result: SweetAlertResult ) => {

					if ( result.isConfirmed ) {
						this.addInventory( result.value, $uiWrapper );
					}

				} );
				
			} )
			
			// Sync subscription prices.
			// TODO: THIS SHOULD GO ON THE syncMainInventoryFields FUNCTION.
			.on( 'change', '.main .wc_input_price', ( evt: JQueryEventObject ) => $( '.wc_input_subscription_price' ).val( $( evt.currentTarget ).val() ) )
			
			// Editable Inventory Names.
			.on( 'keydown', '.inventory-name .editable', ( evt: JQueryEventObject ) => {
				
				const $name = $( evt.currentTarget );
				
				// Hitting enter or esc keys, saves the name.
				if ( evt.keyCode === 13 || evt.keyCode === 27 ) {
					evt.preventDefault();
					$name.blur();
					return false;
				}
				
				$name.addClass( 'dirty' );
				
			} )
			
			// Save the edited Inventory Name.
			.on( 'blur', '.inventory-name .editable', ( evt: JQueryEventObject ) => {
				
				const $name: JQuery = $( evt.currentTarget );
				
				if ( $name.hasClass( 'dirty' ) ) {
					
					const newName: string    = $name.text().trim(),
					      $nameInput: JQuery = $name.closest( '.inventory-group' ).find( '.inventory-name-input' );
					
					$name.removeClass( 'dirty' );
					
					// The edited name must be unique and not empty.
					if ( ! newName ) {
						
						Swal.fire( {
							title            : this.settings.get( 'nameInvalid' ),
							text             : this.settings.get( 'mustHaveName' ),
							icon             : 'error',
							confirmButtonText: this.settings.get( 'ok' ),
						} )
						// Restore the previous value.
						.then( () => $name.text( $nameInput.val() ) );
						
					}
					else {
						
						const $uiWrapper: JQuery     = $name.closest( '.multi-inventory-fields' ),
						      $matchingNames: JQuery = $uiWrapper.find( '.inventory-name > span' ).not( $name ).filter( ( index: number, elem: Element ) => {
							      return $( elem ).text().trim().toLowerCase() === newName.toLowerCase();
						      } );
						
						// Not unique.
						if ( $matchingNames.length ) {
							
							Swal.fire( {
								title            : this.settings.get( 'nameInvalid' ),
								text             : this.settings.get( 'nameAlreadyUsed' ),
								icon             : 'error',
								confirmButtonText: this.settings.get( 'ok' ),
							} )
							// Restore the previous value.
							.then( () => $name.text( $nameInput.val() ) );
							
						}
						// Save the name to the name input.
						else {
							$nameInput.val( newName );
						}
						
					}
				}
			} )
			
			// "Select All" button.
			.on( 'click', '.toggle-checkboxes', ( evt: JQueryEventObject ) => {
				
				const $button: JQuery    = $( evt.currentTarget ),
				      $uiWrapper: JQuery = $button.closest( '.multi-inventory-fields' );
				
				this.toggleInventoryCheckboxes( $uiWrapper, $button.text().trim() === this.settings.get( 'selectAll' ) ? 'select' : 'unselect' );
				
			} )
			
			// "Expand All" button.
			.on( 'click', '.toggle-expanded', ( evt: JQueryEventObject ) => {
				
				let $button: JQuery          = $( evt.currentTarget ),
				    $miWrapper: JQuery       = $button.closest( '.multi-inventory-fields' ),
				    $inventoryGroups: JQuery = $miWrapper.find( '.inventory-group' );
				
				if ( $button.text() === this.settings.get( 'expandAll' ) ) {
					
					$inventoryGroups = $inventoryGroups.filter( ( index: number, elem: Element ) => {
						return $( elem ).find( '.inventory-name' ).hasClass( 'collapsed' )
					} );
					
					$button.text( this.settings.get( 'collapseAll' ) );
					
				}
				else {
					
					$inventoryGroups = $inventoryGroups.filter( ( index: number, elem: Element ) => {
						return ! $( elem ).find( '.inventory-name' ).hasClass( 'collapsed' )
					} );
					
					$button.text( this.settings.get( 'expandAll' ) );
					
				}
				
				this.toggleInventory( $inventoryGroups );
				
			} )
			
			// Inventory selector checkboxes.
			.on( 'change', 'input.inventory-selector', ( evt: JQueryEventObject ) => {
				
				const $checkbox: JQuery       = $( evt.currentTarget ),
				      $inventoryGroup: JQuery = $checkbox.closest( '.inventory-group' );
				
				if ( $checkbox.is( ':checked' ) ) {
					$inventoryGroup.addClass( 'selected' );
				}
				else {
					$inventoryGroup.removeClass( 'selected' );
				}
				
			} )
			
			// Bulk actions.
			.on( 'click', '.apply-bulk', ( evt: JQueryEventObject ) => {
				
				const $button: JQuery    = $( evt.currentTarget ),
				      $uiWrapper: JQuery = $button.closest( '.multi-inventory-fields' ),
				      bulkAction: string = $button.siblings( '.mi-bulk-action' ).val();
				
				let $selectedInventories: JQuery = $uiWrapper.find( '.inventory-group.selected' );
				
				if ( ! $selectedInventories.length ) {
					
					Swal.fire( {
						icon             : 'warning',
						text             : this.settings.get( 'selectInventories' ),
						confirmButtonText: this.settings.get( 'ok' ),
					} );
					
				}
				else if ( ! bulkAction ) {
					
					Swal.fire( {
						icon             : 'warning',
						text             : this.settings.get( 'selectBulkAction' ),
						confirmButtonText: this.settings.get( 'ok' ),
					} );
					
				}
				else {
					
					// Do the chosen action in bulk.
					switch ( bulkAction ) {
						
						case 'clone':
							$selectedInventories.find( '.clone-inventory' ).click();
							this.toggleInventoryCheckboxes( $uiWrapper, 'unselect' );
							break;
						
						case 'clear':
							this.maybeClearInventory( $selectedInventories );
							this.toggleInventoryCheckboxes( $uiWrapper, 'unselect' );
							break;
						
						case 'write-off':
						case 'unwrite-off':
							const writeOffPromise: any = this.writeOffInventory( $selectedInventories, true, bulkAction === 'write-off' ? 1 : 0 );
							
							if ( typeof writeOffPromise === 'function' ) {
								writeOffPromise.then( () => this.toggleInventoryCheckboxes( $uiWrapper, 'unselect' ) );
							}
							else {
								this.toggleInventoryCheckboxes( $uiWrapper, 'unselect' );
							}
							break;
						
						case 'remove':
							// The main inventory cannot be removed.
							$selectedInventories = $selectedInventories.not( '.main' );
							this.removeInventory( $selectedInventories );
							this.toggleInventoryCheckboxes( $uiWrapper, 'unselect' );
							break;
						
					}
					
				}
				
			} )
			
			// Remove/Write-Off inventory.
			.on( 'click', '.controls-bar .remove-inventory', ( evt: JQueryEventObject ) => this.removeOrWriteOff( $( evt.currentTarget ).closest( '.inventory-group' ) ) )
			
			// Clone inventory.
			.on( 'click', '.controls-bar .clone-inventory', ( evt: JQueryEventObject ) => {
				
				const $button: JQuery       = $( evt.currentTarget ),
				      $inventory: JQuery    = $button.closest( '.inventory-group' ),
				      inventoryName: string = $inventory.find( '.editable' ).text().trim(),
				      clonedInvName: string = inventoryName.indexOf( `(${ this.settings.get( 'cloned' ) })` ) > -1 ? inventoryName : `${ inventoryName } (${ this.settings.get( 'cloned' ) })`;
				
				this.addInventory( clonedInvName, $button.closest( '.multi-inventory-fields' ), $inventory, false );
				
				// Scroll to the cloned inventory.
				$( 'html' ).animate( {
					scrollTop: $inventory.siblings( '.inventory-group' ).last().offset().top - 32, // 32px is the admin bar height.
				}, 600 );
				
			} )
			
			// Clear inventory.
			.on( 'click', '.controls-bar .clear-inventory', ( evt: JQueryEventObject ) => {
				
				const $button: JQuery    = $( evt.currentTarget ),
				      $inventory: JQuery = $button.closest( '.inventory-group' );
				
				this.maybeClearInventory( $inventory );
				
			} )
			
			// Manage stock fields on extra Inventories.
			.on( 'change', '.manage_mi_stock input:checkbox', ( evt: JQueryEventObject ) => {
				
				const $checkbox: JQuery       = $( evt.currentTarget ),
				      $inventoryGroup: JQuery = $checkbox.closest( '.inventory-group' );
				
				if ( $checkbox.is( ':checked' ) ) {
					
					$inventoryGroup.find( 'div.inventory_stock_fields' ).show();
					$inventoryGroup.find( 'p.inventory_stock_status_field' ).hide();
					$inventoryGroup.find( '.inv-stock-amount' ).removeClass( 'hidden' );
					$inventoryGroup.find( '._out_stock_threshold_field' ).show();
					
				}
				else {
					
					let $stockStatusField: JQuery = $inventoryGroup.find( 'p.inventory_stock_status_field' );
					
					$inventoryGroup.find( 'div.inventory_stock_fields' ).hide();
					$inventoryGroup.find( '.inv-stock-amount' ).addClass( 'hidden' );
					$inventoryGroup.find( '._out_stock_threshold_field' ).hide();
					
					if ( ! $checkbox.closest( '.woocommerce_variation' ).length ) {
						$stockStatusField = $stockStatusField.not( `.hide_if_${ $( 'select#product-type' ).val() }` );
					}
					
					$stockStatusField.show();
					
				}
				
			} )
			
			// Restore sale price field validation from woocommerce_admin.js.
			.on( 'keyup change', '.inventory-pricing [class*="_sale_price"] input[type=text]', ( evt: JQueryEventObject ) => {
				
				const $salePriceInput: JQuery    = $( evt.currentTarget ),
				      $regularPriceInput: JQuery = $salePriceInput.closest( '.inventory-pricing' ).find( '[class*="_regular_price"] input[type=text]' ),
				      salePrice: number          = <number>Utils.unformat( $salePriceInput.val(), window[ 'woocommerce_admin' ].mon_decimal_point ),
				      regularPrice: number       = <number>Utils.unformat( $regularPriceInput.val(), window[ 'woocommerce_admin' ].mon_decimal_point );
				
				if ( 'keyup' === evt.type ) {
					$( document.body ).triggerHandler( salePrice >= regularPrice ? 'wc_add_error_tip' : 'wc_remove_error_tip', [ $salePriceInput, 'i18n_sale_less_than_regular_error' ] );
				}
				else if ( salePrice >= regularPrice ) {
					$salePriceInput.val( '' );
				}
				
			} )
			
			// Create the MI UI's for variations once loaded or added.
			.on( 'woocommerce_variations_added woocommerce_variations_loaded', ( evt: JQueryEventObject ) => {
				
				this.bindMiSettings();

				// TODO: IF THE STOCK IS MANAGED BY THE VARIABLE AND VARIATION HAS THE MANAGE STOCK DISABLED, SHOULD WE CREATE THE MI UI ON THE VARIABLE?
				$( '.woocommerce_variations' ).find( '.woocommerce_variation' ).each( ( index: number, elem: Element ) => {

					const $variation: JQuery = $( elem );
					const vindex:number = $variation.index();

					this.isUIPrepared[ vindex ] = false;

					// If this variation was already initialised, there is not reason to do it again.
					if ( $variation.hasClass('mi-init') ) {
						return;
					}
					
					this.switchMultiInventory( $variation.find( '.multi_inventory_buttons input:radio:checked' ) );
					
					// Show/Hide the sale price dates per inventory.
					$variation.find( '.sale_price_dates_fields' ).each( ( index: number, elem: Element ) => {
						
						let $theseSaleDates: JQuery  = $( elem ),
						    saleScheduleSet: boolean = false,
						    $wrap: JQuery            = $theseSaleDates.closest( 'div, table' );
						
						$theseSaleDates.find( 'input' ).each( ( index: number, elem: Element ) => {
							if ( '' !== $( elem ).val() ) {
								saleScheduleSet = true;
							}
						} );
						
						if ( saleScheduleSet ) {
							$wrap.find( '.sale_schedule' ).hide();
							$wrap.find( '.sale_price_dates_fields' ).show();
						}
						else {
							$wrap.find( '.sale_schedule' ).show();
							$wrap.find( '.sale_price_dates_fields' ).hide();
						}
						
					} );
					
					// Wait until WC has changed the fields' visibility.
					setTimeout( () => {

						$variation.addClass('mi-init').find( '.manage_mi_stock input:checkbox' ).change();

						// Only reset the "variation update needed" when loading the variations.
						if ( 'woocommerce_variations_loaded' === evt.type ) {
							this.disableVariationUpdateNeeded( $variation );
						}

					}, 500 );
					
				} );
				
			} )
			
			// Restore the WC fields' visibility after checking the Virtual or Downloadable checkboxes.
			.on( 'change', '#_virtual, #_downloadable', () => this.switchMultiInventory( this.$atumPanel.find( '.multi_inventory_buttons input:radio:checked' ), '', 0 ) )
			
			// Show/Hide the "expiry days" field depending on the BBE date field value.
			.on( 'atum-dp-change', '.bbe-date-field .atum-datepicker', ( evt: JQueryEventObject ) => {
				
				const $bbeDatePicker: JQuery       = $( evt.currentTarget ),
				      $bbeInput: JQuery            = $bbeDatePicker.find( 'input' ),
				      $wrapper: JQuery             = $bbeDatePicker.closest( '.woocommerce_variation' ).length ? $bbeDatePicker.closest( '.woocommerce_variation' ) : $bbeDatePicker.closest( '#woocommerce-product-data' ),
				      expirableInventories: string = $wrapper.find( '._expirable_inventories_field input:checked' ).val(),
				      showField: boolean           = $bbeInput.val() && ( 'yes' === expirableInventories || ( 'global' === expirableInventories && 'yes' === this.settings.get( 'defaultExpirableInventories' ) ) );
				
				$bbeInput.closest( '.info-fields' ).find( '.expiry-days-field' ).css( 'display', showField ? 'flex' : 'none' );
				
			} )

			// On variations we need to force the update when any datepickers within the inventory info fields get changed.
			.on( 'atum-dp-change', ( evt: JQueryEventObject ) => {

				const $variation: JQuery = $( evt.target ).closest( '.woocommerce_variation' );

				if ( $variation.length ) {
					this.enableVariationUpdateNeeded( $variation );
				}

			} );
		
		// Wait until WC has changed the fields' visibility.
		setTimeout( () => {

			this.$inventoryPanel.find( '.manage_mi_stock input:checkbox' ).change();

			// Hide Inventory fields for translations.
			if ( typeof this.settings.get( 'isTranslation' ) !== 'undefined' ) {
				const $uiWrapper: JQuery = $( '.woocommerce_variations' ).length ? $( '.woocommerce_variations' ) : this.$inventoryPanel;
				this.toggleWCFields( $uiWrapper, false );
			}

		}, 500 );
		
	}
	
	/**
	 * Bind the MI UI settings
	 * This must be handled apart because when the variations are added to a new variable, it was not triggering.
	 */
	bindMiSettings() {
		
		//
		// Multi-inventory settings at product level.
		// ------------------------------------------
		$( '#atum_product_data, .woocommerce_variations' )
			
			// Sorting inventory mode at product level switch.
			.on( 'change', '._inventory_sorting_mode_field input:radio', ( evt: JQueryEventObject ) => {
				
				const $input: JQuery     = $( evt.currentTarget ),
				      $uiWrapper: JQuery = $input.closest( '.woocommerce_variation' ).length ? $input.closest( '.woocommerce_variation' ) : this.$inventoryPanel;
				
				this.sortInventories( $uiWrapper );
				
			} )
			
			// Multi-Inventory at produt level switch.
			.on( 'change', '.multi_inventory_buttons input:radio', ( evt: JQueryEventObject ) => this.switchMultiInventory( $( evt.currentTarget ) ) )
			
			// Price per Inventory at product level switch.
			.on( 'change', '._price_per_inventory_field input:radio', ( evt: JQueryEventObject ) => this.switchPricePerInventory( $( evt.currentTarget ) ) )

			// Selectable Inventories at product level switch.
			.on( 'change', '._selectable_inventories_field input:radio', ( evt:JQueryEventObject ) => this.switchSelectableInventories( $( evt.currentTarget ) ) );
		
	}
	
	/**
	 * Prepare the Multi-Inventory UI
	 *
	 * @param {JQuery} $uiWrapper The MI UI wrapper being used.
	 * @param {number} delay      Optional. The number of milliseconds before toggling the WC fields visibility.
	 */
	prepareUI( $uiWrapper: JQuery, delay: number = 100 ) {
		
		$uiWrapper.find( '.multi-inventory-panel' ).show();
		
		// Wait until WC has changed the fields' visibility and hide the WC fields.
		setTimeout( () => this.toggleWCFields( $uiWrapper, false ), delay );

		if ( this.isUIPrepared[ $uiWrapper.index() ] ) {
			return;
		}

		this.editPopovers.bindPopovers( $uiWrapper.find( '.atum-edit-field' ) );
		this.dateTimePicker.addDateTimePickers( $uiWrapper.find( '.atum-datepicker' ) );
		this.sortInventories( $uiWrapper );
		this.tooltip.addTooltips( $uiWrapper );
		this.syncMainInventoryFields( $uiWrapper );
		
		/*this.enhancedSelect.doSelect2( $uiWrapper.find( '.atum-select2' ), {
			minimumResultsForSearch: 20,
		} );*/
		
		// Open first inventory (if closed).
		if ( $uiWrapper.find( '.inventory-group' ).length ) {
			const $firstInventory: JQuery =  $uiWrapper.find( '.inventory-group' ).first();

			if ( $firstInventory.find( '.inventory-name' ).hasClass( 'collapsed' ) ) {
				this.toggleInventory( $firstInventory );
			}
		}

		this.isUIPrepared[ $uiWrapper.index() ] = true;
		
	}
	
	/**
	 * Toggle the WC fields' visibility
	 *
	 * @param {JQuery}  $uiWrapper The MI UI wrapper being used.
	 * @param {boolean} visible    Whether the matched fields should be shown or hidden.
	 */
	toggleWCFields( $uiWrapper: JQuery, visible: boolean ) {
		
		if ( this.settings.get( 'fieldsToHide' ).length ) {
			
			$.each( this.settings.get( 'fieldsToHide' ), ( index: number, selector: string ) => {
				
				if ( selector.indexOf( '{%d}' ) > -1 ) {
					
					if ( ! $uiWrapper.hasClass( 'woocommerce_variation' ) ) {
						return;
					}
					
					selector = selector.replace( '{%d}', $uiWrapper.index( '.woocommerce_variation' ).toString() );
				}
				
				if ( $uiWrapper.find( '.multi-inventory-panel' ).length ) {
					
					let $field: JQuery = $uiWrapper.find( selector );

					if ( '.shipping_class_field' === selector )
						$field = this.$shippingPanel.find( selector );

					if ( $field.length ) {
						
						// As the WC fields are always upper in the DOM, the first one is the only we need to hide.
						if ( $field.length > 1 ) {
							$field = $field.first();
						}
						
						let $selector: JQuery;

						// Special case for variation shipping class fields (no way to select its ancestor).
						if ( selector.indexOf( 'variable_shipping_class' ) > -1 ) {
							$selector = $field.closest( '.form-row' );
						}
						else if ( $field.closest( '.options_group' ).length ) {
							$selector = $field.closest( '.options_group' );
						}
						else {
							$selector = $field;
						}
						
						// TODO: CHECK MANAGE STOCK OPTION VALUE.
						if ( visible === true ) {
							$selector.show();
						}
						else {
							$selector.hide();
						}
						
					}
				}
				
			} );
			
		}
		
	}
	
	/**
	 * Sync the WC and main multi-inventory fields
	 *
	 * @param {JQuery} $uiWrapper
	 */
	syncMainInventoryFields( $uiWrapper: JQuery ) {
		
		let $mainInventory: JQuery = $uiWrapper.find( '.inventory-group.main' ),
		    $fieldsToSync: JQuery  = $mainInventory.find( '[data-sync]' ),
		    $wcFieldsToSync: any   = $fieldsToSync;
		
		// Add all the WC fields that will be synced to the array.
		$fieldsToSync.each( ( index: number, elem: Element ) => {
			
			const $originField: JQuery = $( elem ),
			      $sourcefield: JQuery = $( $originField.data( 'sync' ) );
			
			if ( $sourcefield.length ) {
				// Add the sync data to the WC fields too, so we know with which field to sync.
				$wcFieldsToSync.push( $sourcefield.data( 'sync', `#${ $originField.attr( 'id' ) }` ).get( 0 ) );
			}
		} );
		
		$wcFieldsToSync.on( 'keyup change paste', ( evt: JQueryEventObject ) => {
			
			const $originField: JQuery = $( evt.currentTarget ),
			      $sourceField: JQuery = $( $originField.data( 'sync' ) );
			
			// Checkboxes.
			if ( $originField.is( ':checkbox' ) ) {
				$sourceField.prop( 'checked', $originField.is( ':checked' ) );
			}
			// Enhanced Selects.
			else if ( $originField.hasClass( 'enhanced' ) ) {
				
				$sourceField.find( 'option' ).remove();
				$sourceField.append( $originField.find( 'option' ).clone() );
				
				$sourceField.val( $originField.val() );
				
			}
			// Rest of fields.
			else {
				$sourceField.val( $originField.val() );
			}
			
		} );
		
	}
	
	/**
	 * Listen changes to the Multi-Inventory status buttons
	 *
	 * @param {JQuery} $input The multi-inventory status input changed.
	 * @param {string} status Optional. Override the $input status value.
	 * @param {number} delay  Optional. The number of milliseconds before toggling the WC fields visibility.
	 */
	switchMultiInventory( $input: JQuery, status: string = '', delay: number = 100 ) {
		
		status = status || $input.val();
		
		const $locationTermsBox: JQuery  = $( '#atum_locationdiv' ), // Product Location taxonomies meta box.
		    $curPanel: JQuery          = $input.closest( '.atum-data-panel' ),
		    $miDependentFields: JQuery = $curPanel.find( '._inventory_sorting_mode_field, ._inventory_iteration_field, ._expirable_inventories_field, ._price_per_inventory_field, ._selectable_inventories_field' ),
		    $uiWrapper: JQuery         = $input.closest( '.woocommerce_variation' ).length ? $input.closest( '.woocommerce_variation' ) : this.$inventoryPanel,
		    isVariation: boolean       = $uiWrapper.hasClass( 'woocommerce_variation' ),
		    productIsParent: boolean   = this.settings.get( 'compatibleParentTypes' ).indexOf( this.currentProductType ) > -1;
		
		if ( productIsParent && ! isVariation ) {
			return;
		}
		
		switch ( status ) {
			
			case 'yes':
				this.prepareUI( $uiWrapper, delay );
				$miDependentFields.slideDown();
				this.switchPricePerInventory( $curPanel.find( '._price_per_inventory_field input:radio:checked' ) );
				this.switchSelectableInventories( $curPanel.find( '._selectable_inventories_field input:radio:checked' ) );
				
				if ( isVariation ) {
					$uiWrapper.find( '.variable_manage_stock' ).parent().hide();
				}
				else {
					$locationTermsBox.hide();
				}
				
				break;
			
			case 'global':
				if ( this.settings.get( 'defaultMultiInventory' ) === 'yes' ) {
					this.prepareUI( $uiWrapper, delay );
					$miDependentFields.slideDown();
					this.switchPricePerInventory( $curPanel.find( '._price_per_inventory_field input:radio:checked' ) );
					this.switchSelectableInventories( $curPanel.find( '._selectable_inventories_field input:radio:checked' ) );
					
					if ( isVariation ) {
						$uiWrapper.find( '.variable_manage_stock' ).parent().hide();
					}
					else {
						$locationTermsBox.hide();
					}
				}
				else {
					$uiWrapper.find( '.multi-inventory-panel' ).hide();
					this.toggleWCFields( $uiWrapper, true );
					$miDependentFields.slideUp();
					this.switchPricePerInventory( $curPanel.find( '._price_per_inventory_field input:radio:checked' ), 'no' );
					this.switchSelectableInventories( $curPanel.find( '._selectable_inventories_field input:radio:checked' ), 'no' );
					
					if ( isVariation ) {
						$uiWrapper.find( '.variable_manage_stock' ).change().parent().show();
					}
					else {
						$locationTermsBox.show();
					}
				}
				
				break;
			
			case 'no':
				$uiWrapper.find( '.multi-inventory-panel' ).hide();
				this.toggleWCFields( $uiWrapper, true );
				$miDependentFields.slideUp();
				this.switchPricePerInventory( $curPanel.find( '._price_per_inventory_field input:radio:checked' ), 'no' );
				this.switchSelectableInventories( $curPanel.find( '._selectable_inventories_field input:radio:checked' ), 'no' );
				
				if ( isVariation ) {
					$uiWrapper.find( '.variable_manage_stock' ).change().parent().show();
				}
				else {
					$locationTermsBox.show();
				}
				
				break;
		}
		
	}
	
	/**
	 * Hide/Show Price Per Inventory
	 *
	 * @param {JQuery} $input
	 * @param {string} value
	 */
	switchPricePerInventory( $input: JQuery, value?: string ) {
		
		const $uiWrapper: JQuery        = $input.closest( '.woocommerce_variation' ).length ? $input.closest( '.woocommerce_variation' ) : this.$inventoryPanel,
		      $wooPricesWrapper: JQuery = $uiWrapper.hasClass( 'woocommerce_variation' ) ? $uiWrapper : this.$productDataBox.find( '#general_product_data' ),
		      showIfCurrent: string     = `show_if_${ this.currentProductType }`;
		
		if ( typeof value === 'undefined' ) {
			value = $input.val() !== 'global' ? $input.val() : this.settings.get( 'defaultPricePerInventory' );
		}
		
		if ( 'no' === value ) {
			$uiWrapper.find( '.inventory-pricing' ).css( 'display', 'none' );
			$wooPricesWrapper.find( `.options_group.pricing.${ showIfCurrent }, .variable_pricing` ).css( 'display', 'block' );
		}
		else {
			$uiWrapper.find( '.inventory-pricing' ).css( 'display', 'block' );
			$wooPricesWrapper.find( `.options_group.pricing.${ showIfCurrent }, .variable_pricing` ).css( 'display', 'none' );
		}
		
	}

	/**
	 * Show/Hide the Inventories Selection Mode field according to Selectable Inventories status
	 *
	 * @param {JQuery} $input
	 * @param {string} value
	 */
	switchSelectableInventories( $input: JQuery, value?: string ) {

		const $uiWrapper: JQuery           = $input.closest( '.woocommerce_variation' ).length ? $input.closest( '.woocommerce_variation' ) : this.$atumPanel,
		      $inventorySelectionModeField = $uiWrapper.find( '._selectable_inventories_mode_field' );

		if ( typeof value === 'undefined' ) {
			value = $input.val() !== 'global' ? $input.val() : this.settings.get( 'defaultSelectableInventories' );
		}

		if ( 'no' === value ) {
			$inventorySelectionModeField.slideUp( 'fast' );
		}
		else {
			$inventorySelectionModeField.slideDown( 'fast' );
		}

	}
	
	/**
	 * Add a new inventory
	 *
	 * @param {string}  invName           The inventory name previously asked with sweet alert.
	 * @param {JQuery}  $uiWrapper        The MI UI wrapper being used.
	 * @param {JQuery}  $inventorySource  Optional. If passed, will use this inventory as source instead of main.
	 * @param {boolean} clearFields       Optional. Whether to clear all the fields of the new inventory. By default will clear.
	 */
	addInventory( invName: string, $uiWrapper: JQuery, $inventorySource ?: JQuery, clearFields ?: boolean ) {
		
		// If no inventorySource is provided, clone the main inventory as template.
		if ( typeof $inventorySource === 'undefined' ) {
			$inventorySource = $uiWrapper.find( '.inventory-group.main' );
		}
		
		const $newInventory: JQuery = $inventorySource.clone();
		$newInventory.removeClass( 'main' );
		
		// Remove non needed fields.
		$newInventory.find( '[name~="_original_stock"]' ).remove();
		$newInventory.find( '.atum-icon.main' ).remove();
		
		// PL Compatibility.
		if ( $newInventory.find( '.bom-stock-control-fields, ._committed_field, ._shortage_field, ._free_to_use_field' ).length ) {
			$newInventory.find( '.bom-stock-control-fields, ._committed_field, ._shortage_field, ._free_to_use_field' ).remove();
			$newInventory.find( '[class^="form-field _stock_"]' ).removeAttr( 'style' );
		}
		
		// Reset and setup all the cloned fields.
		if ( typeof clearFields === 'undefined' || clearFields === true ) {
			this.clearInventoryFields( $newInventory );
		}
		// The inventory date cannot be cloned.
		else {
			const $dateField: JQuery = $newInventory.find( '.inventory-date-field' );
			$dateField.find( 'input' ).val( '' );
			$dateField.find( '.field-label' ).text( this.settings.get( 'none' ) );
		}
		
		// Complete the new inventory setup.
		const $newInventoryName: JQuery = $newInventory.find( '.inventory-name' );
		$newInventory.removeData( 'id' ).removeAttr( 'data-id' ).removeClass( 'instock outofstock onbackorder main expired selected' ).addClass( 'new' );
		$newInventoryName.removeClass( 'collapsed' );
		$newInventory.find( '.inventory-fields' ).removeAttr( 'style' );
		$newInventoryName.find( '.editable' ).text( invName );
		$newInventoryName.find( '.inventory-status' ).text( '' );
		$newInventory.find( '.inventory-name-input' ).val( invName );
		$newInventory.find( '.is-main-input' ).remove();
		$newInventory.find( '[id^="_purchase_price"]' ).prop( 'disabled', false );
		
		$newInventory.insertAfter( $uiWrapper.find( '.inventory-group' ).last() );
		this.resetInventoryFieldIdentifiers( $newInventory ); // This must be executed once it's added to the DOM.
		
		// Re-bind the inventory components.
		this.maybeRestoreEnhancedSelects( $newInventory.find( '.enhanced' ) );
		$newInventory.find( ':input' ).change();
		
		const id: string                       = $newInventory.find( '.atum-edit-field' ).data( 'content-id' ),
		      $containerSelectTemplate: JQuery = $( `#${ id }` ),
		      $selectTemplate: any[]           = $.parseHTML( $containerSelectTemplate.html() );
		
		$containerSelectTemplate.html( '' );
		$containerSelectTemplate.append( $selectTemplate );
		
		const options = $containerSelectTemplate.find( 'option' );
		options.each( ( index: number, elem: Element ) => {
			$( elem ).removeAttr( 'selected' );
		} );
		
		this.dateTimePicker.addDateTimePickers( $newInventory.find( '.atum-datepicker' ) );
		this.editPopovers.bindPopovers( $newInventory.find( '.atum-edit-field' ) );
		this.tooltip.addTooltips( $uiWrapper );

		this.$atumPanel.trigger( 'atum-mi-added-inventory', [ $newInventory ] );

		return $newInventory;
		
	}
	
	/**
	 * Ask the user if definitely want to clear all the inventory fields
	 *
	 * @param {JQuery} $inventory The inventory to clear.
	 */
	maybeClearInventory( $inventory: JQuery ) {
		
		Swal.fire( {
			title             : this.settings.get( 'areYouSure' ),
			icon              : 'warning',
			text              : $inventory.length === 1 ? this.settings.get( 'confirmClearing' ) : this.settings.get( 'confirmClearingMulti' ),
			showCancelButton  : true,
			confirmButtonText : this.settings.get( 'clearThem' ),
			confirmButtonColor: '#EFAF00',
			cancelButtonText  : this.settings.get( 'cancel' ),
			cancelButtonColor : '#FF4848',
		} )
		.then( ( result: SweetAlertResult ) => {

			if ( result.isConfirmed ) {
				this.clearInventoryFields( $inventory );
				this.resetInventoryFieldIdentifiers( $inventory );
				this.maybeRestoreEnhancedSelects( $inventory.find( '.enhanced' ) );
			}
			
		} );
		
	}
	
	/**
	 * Clear all the fields and info of the specified inventory
	 *
	 * @param {JQuery} $inventory The inventory to clear.
	 */
	clearInventoryFields( $inventory: JQuery ) {
		
		// Get all the fields except inventory name.
		const $inventoryFields = $inventory.find( ':input' ).not( '[data-allow-clear="no"]' );
		
		// Clear the fields.
		$inventoryFields.each( ( index: number, elem: Element ) => {
			
			const $elem: JQuery = $( elem ),
			      idAtt: string = $elem.attr( 'id' );
			
			if ( $elem.is( ':checkbox' ) ) {
				
				let checked = false;
				
				// Enable the manage stock checkbox.
				if ( typeof idAtt !== 'undefined' && idAtt.indexOf( '_manage_stock' ) > -1 ) {
					checked = true;
					
					// Add custom classes to the cloned inventory fields.
					$elem.closest( '.form-field' ).addClass( 'manage_mi_stock' )
						.siblings( '.stock_fields' ).toggleClass( 'stock_fields inventory_stock_fields' )
						.siblings( '.stock_status_field' ).toggleClass( 'stock_status_field inventory_stock_status_field' );
				}
				
				$elem.prop( 'checked', checked );
				
			}
			else {
				$elem.val( ( ( $elem.is( 'select' ) && ! $elem.hasClass( 'enhanced' ) ) ? $elem.find( 'option' ).first().val() : '' ) );
			}
			
			// Destroy enhanced selects.
			if ( $elem.hasClass( 'enhanced' ) ) {
				$elem.removeClass( 'enhanced' );
				$elem.siblings( '.select2-container' ).remove();
			}
			
			// Propagate the changes.
			$elem.change();
			
		} );
		
		// Clear the inventory info.
		$inventory.find( '.woocommerce-help-tip' ).remove();
		$inventory.find( '.region-field, .location-field, .bbe-date-field, .lot-field' ).find( '.field-label' ).removeClass( 'unsaved' ).text( this.settings.get( 'none' ) );
		$inventory.find( '.priority-field, .inventory-date-field' ).addClass( 'no-icon' ).find( '.field-label' ).addClass( 'unsaved' ).text( this.settings.get( 'saveToSet' ) );
		$inventory.find( '.inventory-date-field .atum-datepicker' ).remove();
		$inventory.removeClass( 'selected' );
		
	}
	
	/**
	 * Reset id and name from the specified inventory's fields
	 *
	 * @param {JQuery} $inventory The inventory to clear.
	 */
	resetInventoryFieldIdentifiers( $inventory: JQuery ) {
		
		const $inventoryFields: JQuery = $inventory.find( ':input' ),
		      inventoryIndex: number   = $inventory.index( '.inventory-group' ),
		      $uiWrapper: JQuery       = $inventory.closest( '.multi-inventory-fields' ),
		      // Changed variation-multi-inventory-panel by woocommerce_variation. Should the panel have the class?
		      isVariation: boolean     = $uiWrapper.closest( '.woocommerce_variation' ).length !== 0;
		
		// Clear the fields.
		$inventoryFields.each( ( index: number, elem: Element ) => {
			
			let $elem: JQuery   = $( elem ),
			    nameAtt: string = $elem.attr( 'name' ),
			    idAtt: string   = $elem.attr( 'id' );
			
			// Do not act on elements that already have the right identifiers.
			if ( ! $inventory.hasClass( 'new' ) ) {
				return;
			}
			
			// Clean up the name first.
			if ( typeof nameAtt !== 'undefined' ) {
				
				nameAtt = nameAtt.replace( /\d|\[|\]|atum_mi|main|new_/g, '' );
				let name: string = 'atum_mi';
				
				// For variations, add the loop index.
				if ( isVariation ) {
					name += `[${ $uiWrapper.closest( '.woocommerce_variation' ).index( '.woocommerce_variation' ) }][new_${ inventoryIndex }][${ nameAtt }]`;
				}
				else {
					name += `[new_${ inventoryIndex }][${ nameAtt }]`;
				}
				
				$elem.attr( 'name', name );
				
			}
			
			if ( typeof idAtt !== 'undefined' ) {
				idAtt = idAtt.replace( /_new.*/g, '' ); // If the sufix was added previously, remove it.
				$elem.attr( 'id', `${ idAtt }_new${ inventoryIndex }` );
			}
			
		} );
		
	}
	
	/**
	 * Remove an inventory
	 *
	 * @param {JQuery} $inventory The inventory to remove/write-off.
	 */
	removeOrWriteOff( $inventory: JQuery ) {
		
		const isWrittenOff: boolean = $inventory.hasClass( 'write-off' );
		
		// If the inventory is still not saved, just remove it from DOM.
		if ( $inventory.hasClass( 'new' ) ) {
			this.tooltip.destroyTooltips( $inventory );
			$inventory.remove();
			
			return;
		}
		
		Swal.fire( {
			title              : this.settings.get( 'inventoryRemoval' ),
			text               : ! isWrittenOff ? this.settings.get( 'removeConfirmation' ) : this.settings.get( 'removeConfirmation2' ),
			icon               : 'warning',
			showCancelButton   : true,
			showCloseButton    : true,
			confirmButtonText  : ! isWrittenOff ? this.settings.get( 'writeOff' ) : this.settings.get( 'unwriteOff' ),
			confirmButtonColor : '#EFAF00',
			cancelButtonText   : this.settings.get( 'remove' ),
			cancelButtonColor  : '#FF4848',
			showLoaderOnConfirm: true,
			allowOutsideClick  : () => !Swal.isLoading(),
			// Confirm button --> Write-Off action.
			preConfirm         : () => this.writeOffInventory( $inventory, false ),
		} )
		.then( ( result: SweetAlertResult ) => {

			if ( result.isConfirmed ) {

				// Inventory marked as Written Off.
				Swal.fire( {
					title            : this.settings.get( 'done' ),
					icon             : 'success',
					text             : result.value.data,
					confirmButtonText: this.settings.get( 'ok' ),
				} )
				.then( () => {
					$inventory.toggleClass( 'write-off' );
					$inventory.find( '.inventory-status' ).children( '.inventory-stock-status' ).text( this.settings.get( 'writeOff' ) );
				} );

			}
			// Cancel button --> Remove action.
			else if ( result.dismiss === Swal.DismissReason.cancel ) {
				this.removeInventory( $inventory );
			}
			
		});
		
	}
	
	/**
	 * Remove the specified inventory
	 *
	 * @param {JQuery} $inventory The inventory to remove.
	 */
	removeInventory( $inventory: JQuery ) {
		
		// Just remove from DOM all the inventories that were not previously saved.
		$inventory.each( ( index: number, elem: Element ) => {
			
			const $curInv: JQuery = $( elem );
			
			if ( $curInv.hasClass( 'new' ) ) {
				this.tooltip.destroyTooltips( $curInv );
				$curInv.remove();
				$inventory = $inventory.not( $curInv );
			}
			
		} );
		
		if ( ! $inventory.length ) {
			return;
		}
		
		// Get confirmation to remove the inventory.
		Swal.fire({
			title              : this.settings.get('areYouSure'),
			text               : $inventory.length === 1 ? this.settings.get('removePermanently') : this.settings.get('removePermanentlyMulti'),
			icon               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get('removeIt'),
			confirmButtonColor : '#FF4848',
			cancelButtonText   : this.settings.get('cancel'),
			showLoaderOnConfirm: true,
			allowOutsideClick  : () => !Swal.isLoading(),
			preConfirm         : () => {
				
				// Try to remove the inventory.
				return new Promise( ( resolve: Function, reject: Function ) => {
					
					$.ajax({
						url       : window['ajaxurl'],
						method    : 'POST',
						dataType  : 'json',
						data      : {
							action      : 'atum_mi_remove_inventory',
							inventory_id: this.getId($inventory),
							token       : $inventory.closest('.multi-inventory-panel').data('nonce')
						},
						success   : ( response: any ) => {

							if ( response.success !== true ) {
								Swal.showValidationMessage( response.data );
							}

							resolve( response.data );
							
						}
					});
					
				});
				
			}
		})
		.then( ( result: SweetAlertResult ) => {

			if ( result.isConfirmed ) {

				// Inventory removed.
				Swal.fire( {
					title            : this.settings.get( 'done' ),
					icon             : 'success',
					text             : result.value,
					confirmButtonText: this.settings.get( 'ok' ),
				} )
				.then( () => {
					this.tooltip.destroyTooltips( $inventory );
					$inventory.remove();
				} );

			}
			
		});
		
	}
	
	/**
	 * Write off the specified inventory
	 *
	 * @param {JQuery}  $inventory    The inventory to write-off.
	 * @param {boolean} showMessage   Whether to show the confirmation message.
	 * @param {int}     forceWriteOff Optional. If passed: 1 = write-off, 0 = unwrite-off.
	 *
	 * @return {Promise|boolean}
	 */
	writeOffInventory( $inventory: JQuery, showMessage: boolean, forceWriteOff?: number ) {
		
		let writeOff: number;
		
		if ( typeof forceWriteOff !== 'undefined' ) {
			writeOff = parseInt( forceWriteOff.toString() );
		}
		else {
			writeOff = $inventory.hasClass( 'write-off' ) ? 0 : 1;
		}
		
		// The inventories that are not saved, do not support the write-off, so exclude them.
		$inventory = $inventory.not( '.new' );
		
		if ( ! $inventory.length ) {
			
			Swal.fire( {
				title            : this.settings.get( 'error' ),
				icon             : 'warning',
				text             : this.settings.get( 'saveBeforeWriteOff' ),
				confirmButtonText: this.settings.get( 'ok' ),
			} );
			
			return false;
			
		}
		
		// Try to write-off the inventory.
		const writeOffPromise = new Promise( ( resolve: Function, reject: Function ) => {
			
			$.ajax( {
				url     : window[ 'ajaxurl' ],
				method  : 'POST',
				dataType: 'json',
				data    : {
					action      : 'atum_mi_write_off_inventory',
					inventory_id: this.getId( $inventory ),
					token       : $inventory.closest( '.multi-inventory-panel' ).data( 'nonce' ),
					write_off   : writeOff,
				},
				success : ( response: any ) => resolve( response ),
			} );
			
		} );
		
		if ( showMessage === true ) {
			
			writeOffPromise.then( ( response: any ) => {
				
				const isSuccess = response.success === true;
				
				if ( isSuccess ) {
					$inventory.each( ( index: number, elem: Element ) => {
						
						let $inv: JQuery   = $( elem ),
						    status: string = '';
						
						if ( writeOff === 1 ) {
							status = this.settings.get( 'writeOff' );
							$inv.addClass( 'write-off' );
						}
						else {
							
							if ( $inv.hasClass( 'instock' ) ) {
								status = this.settings.get( 'inStock' );
							}
							else {
								status = this.settings.get( 'outOfStock' );
							}
							
							$inv.removeClass( 'write-off' );
							
						}
						
						$inv.find( '.inventory-status' ).children( '.inventory-stock-status' ).text( status );
						
					} );
				}
				
				// Inventory Write Off confirmation.
				Swal.fire( {
					title            : isSuccess ? this.settings.get( 'done' ) : this.settings.get( 'error' ),
					icon             : isSuccess ? 'success' : 'error',
					text             : response.data,
					confirmButtonText: this.settings.get( 'ok' ),
				} );
				
			} );
			
		}
		
		return writeOffPromise;
		
	}
	
	/**
	 * Get the ID(s) of the specified inventory(ies)
	 *
	 * @param {JQuery} $inventory
	 *
	 * @return {string}
	 */
	getId( $inventory: JQuery ) {
		
		let inventoryIds: string[] = [];
		
		$inventory.each( ( index: number, elem: Element ) => {
			inventoryIds.push( $( elem ).data( 'id' ) );
		} );
		
		return inventoryIds.join( ',' );
		
	}
	
	/**
	 * Rearrange the priorities for the inventories
	 *
	 * @param {JQuery} $uiWrapper
	 */
	sortInventories( $uiWrapper: JQuery ) {
		
		let $inventoryGroups: JQuery    = $uiWrapper.find( '.multi-inventory-panel .inventory-group' ),
		    $sortingModeWrapper: JQuery = $uiWrapper.hasClass( 'woocommerce_variation' ) ? $uiWrapper : $( '#atum_product_data' ),
		    sortingMode: string         = $sortingModeWrapper.find( '._inventory_sorting_mode_field input:radio:checked' ).val();
		
		if ( sortingMode === 'global' ) {
			sortingMode = this.settings.get( 'defaultSortingMode' );
		}
		
		if ( ! $inventoryGroups.length ) {
			return;
		}
		
		let inventoryData: any[]    = [],
		    inventoryGroupsObj: any = {};
		
		$inventoryGroups.each( ( index: number, elem: Element ) => {
			
			const $inventoryGroup: JQuery = $( elem ).detach(),
			      inventoryId: string     = $inventoryGroup.data( 'id' );
			
			inventoryGroupsObj[ inventoryId ] = $inventoryGroup;
			
			inventoryData.push( {
				id      : inventoryId,
				date    : $inventoryGroup.find( '.inventory-date-field' ).find( '.field-label' ).text().trim(),
				bbeDate : $inventoryGroup.find( '.bbe-date-field' ).find( 'input:text' ).val(),
				priority: parseInt( $inventoryGroup.find( '.priority-field' ).find( 'input:hidden' ).val() ),
			} );
			
		} );
		
		switch ( sortingMode ) {
			
			// Last added sells first.
			case 'lifo':
				inventoryData.sort( ( a: any, b: any ) => {
					
					if ( a.date === b.date ) {
						return 0;
					}
					
					if ( a.date === '' ) {
						return -1;
					}
					
					if ( b.date === '' ) {
						return 1;
					}
					
					return new Date( b.date ).getTime() - new Date( a.date ).getTime();
					
				} );
				
				break;
			
			// The shortest lifespan (nearest BBE date) sells first.
			case 'bbe':
				inventoryData.sort( ( a: any, b: any ) => {
					
					if ( a.bbeDate === b.bbeDate ) {
						return 0;
					}
					
					if ( a.bbeDate === '' ) {
						return 1;
					}
					
					if ( b.bbeDate === '' ) {
						return -1;
					}
					
					return new Date( a.bbeDate ).getTime() - new Date( b.bbeDate ).getTime();
					
				} );
				
				break;
			
			// Set manually by user.
			case 'manual':
				inventoryData.sort( ( a: any, b: any ) => {
					return a.priority - b.priority;
				} );
				
				break;
			
			// First added sells first.
			case 'fifo':
			default:
				inventoryData.sort( ( a: any, b: any ) => {
					
					if ( a.date === b.date ) {
						return 0;
					}
					
					if ( a.date === '' ) {
						return 1;
					}
					
					if ( b.date === '' ) {
						return -1;
					}
					
					return new Date( a.date ).getTime() - new Date( b.date ).getTime();
					
				} );
				
				break;
			
		}
		
		// Re-add the inventories with the right order.
		$.each( inventoryData, ( index: string, elem: any ) => {
			inventoryGroupsObj[ elem.id ].insertBefore( $uiWrapper.find( '.add-inventory' ) );
		} );
		
		// Add the drag handler when manual priority.
		if ( sortingMode === 'manual' ) {
			$uiWrapper.find( '.inventory-name:not(:has(.drag-item))' ).prepend( '<span class="drag-item">...</span>' );
			this.doSortable( $uiWrapper );
		}
		else {
			$uiWrapper.find( '.drag-item' ).remove();
		}
		
		this.setPriorities( $uiWrapper );
		
	}
	
	/**
	 * Allow inventories to be manually sortables
	 *
	 * @param {JQuery} $uiWrapper
	 */
	doSortable( $uiWrapper: JQuery ) {
		
		( <any>$( '.multi-inventory-fields' ) ).sortable( {
			handle              : '.drag-item',
			items               : '.inventory-group',
			forcePlaceholderSize: true,
			stop                : ( event: any, ui: any ) => {

				this.setPriorities( $uiWrapper );

				const $variation: JQuery = $( ui.item ).closest( '.woocommerce_variation' );

				if ( $variation.length ) {
					this.enableVariationUpdateNeeded( $variation );
				}

			},
		} );
		
	}
	
	/**
	 * Set the selling priorities for each inventory
	 *
	 * @param {JQuery} $uiWrapper
	 */
	setPriorities( $uiWrapper: JQuery ) {
		$uiWrapper.find( '.inventory-group' ).each( ( index: number, elem: Element ) => {
			$( elem ).find( '.priority-field input:hidden' ).val( index )
				.siblings( '.field-label' ).text( index );
		} );
	}
	
	/**
	 * Toggle an inventory section
	 *
	 * @param {JQuery} $inventory
	 */
	toggleInventory( $inventory: JQuery ) {

		const $inventoryFields: JQuery = $inventory.find( '.inventory-fields' );
		$inventory.find( '.inventory-name' ).toggleClass( 'collapsed' );
		$inventoryFields.slideToggle();
		
	}
	
	/**
	 * Select/Unselect all the inventories from their checkboxes
	 *
	 * @param jQuery $uiWrapper  The MI UI wrapper.
	 * @param string [action]    Optional. The action to be performed (select or unselect).
	 */
	toggleInventoryCheckboxes( $uiWrapper: JQuery, action: string = '' ) {
		
		const $button: JQuery     = $uiWrapper.find( '.toggle-checkboxes' ),
		      $checkboxes: JQuery = $uiWrapper.find( 'input.inventory-selector' );
		
		if ( action === 'select' ) {
			$checkboxes.prop( 'checked', true ).change();
			$button.text( this.settings.get( 'unselectAll' ) );
		}
		else {
			$checkboxes.prop( 'checked', false ).change();
			$button.text( this.settings.get( 'selectAll' ) );
		}
		
	}
	
	/**
	 * Restore the enhanced selects (if any)
	 *
	 * @param {JQuery} $selects
	 */
	maybeRestoreEnhancedSelects( $selects: JQuery ) {
		
		// Remove the enhanced selects.
		$( '.select2-container--open' ).remove();
		
		$selects.each( ( index: number, elem: Element ) => {
			
			const $select: JQuery = $( elem );
			
			if ( $select.hasClass( 'enhanced' ) ) {
				$select.removeClass( 'enhanced' ).siblings( '.select2-container' ).remove();
			}
			
		} );
		
		// Regenerate them.
		$( 'body' ).trigger( 'wc-enhanced-select-init' );
		
	}

	/**
	 * Sometimes we need to trigger the .change() for any input without forcing the variation to be updated
	 * So we need to remove the class being added by WC to avoid this issue.
	 *
	 * @param {JQuery} $variation
	 */
	disableVariationUpdateNeeded( $variation: JQuery ) {

		$variation.removeClass( 'variation-needs-update' );

		if ( ! this.$productDataBox.find( '.variation-needs-update' ).length ) {
			this.$productDataBox.find( 'button.cancel-variation-changes, button.save-variation-changes' ).prop( 'disabled', true );
		}

	}

	/**
	 * Also, there are changes that are not triggering any "change" event and WC is not aware of it, so the variations gets not updated.
	 *
	 * @param {JQuery} $variation
	 */
	enableVariationUpdateNeeded( $variation: JQuery ) {

		$variation.addClass( 'variation-needs-update' );
		this.$productDataBox.find( 'button.cancel-variation-changes, button.save-variation-changes' ).prop( 'disabled', false );

	}
	
}