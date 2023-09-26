/* =======================================
   ADD INVENTORY MODAL
   ======================================= */

import DateTimePicker from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_date-time-picker';
import Settings from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';
import Swal from 'sweetalert2';

export default class AddInventoryModal {
	
	$modal: JQuery;
	
	constructor(
		private settings: Settings,
		private dateTimePicker: DateTimePicker,
		private productId: number,
		private productName: string
	) {
		
		// Show the modal directly.
		this.showModal();
		
	}
	
	/**
	 * Show the modal
	 */
	showModal() {
		
		Swal.fire( {
			title              : this.productName,
			html               : $( '#add-inventory-modal' ).html(),
			showCancelButton   : false,
			showCloseButton    : true,
			confirmButtonText  : this.settings.get( 'createInventory' ),
			confirmButtonColor : '#69c61d',
			customClass        : {
				container: 'add-inventory-modal atum-modal',
			},
			showLoaderOnConfirm: true,
			allowOutsideClick  : false,
			allowEnterKey      : false,
			didOpen            : ( modal: HTMLElement ) => {
				
				this.$modal = $( modal );
				this.dateTimePicker.addDateTimePickers( this.$modal.find( '.atum-datepicker' ) );
				this.bindEvents();
				
			},
			preConfirm         : (): Promise<void> => this.createInventory(),
		} );
		
	}

	/**
	 * Bind events
	 */
	bindEvents() {

		// Enable the enhanced selects.
		$( 'body' ).trigger( 'wc-enhanced-select-init' );

		this.$modal

			// Show hide the stock fields when changing the "manage stock" field.
			.on( 'change', '#inventory-manage_stock', ( evt: JQueryEventObject ) => {

				const $dropdown: JQuery = $( evt.currentTarget );
				let hiddenGroups: string,
				    shownGroups: string;

				if ( $dropdown.val() === 'yes' ) {
					hiddenGroups = '.manage-stock__disabled';
					shownGroups = '.manage-stock__enabled';
				}
				else {
					hiddenGroups = '.manage-stock__enabled';
					shownGroups = '.manage-stock__disabled';
				}

				this.$modal.find( hiddenGroups ).hide().find( ':input' ).prop( 'disabled', true );
				this.$modal.find( shownGroups ).show().find( ':input' ).prop( 'disabled', false );

			} );

	}

	/**
	 * Create a new inventory via ajax
	 */
	createInventory(): Promise<void> {

		return new Promise( ( resolve: Function ) => {

			// Validate the fields before submitting the request.
			if ( !this.$modal.find( '#inventory-name' ).val() ) {
				Swal.showValidationMessage( this.settings.get( 'nameRequired' ) );
				this.$modal.find( '#inventory-name' ).focus().select();
				resolve();
				return;
			}

			$.ajax( {
				url     : window[ 'ajaxurl' ],
				data    : {
					action        : 'atum_mi_create_inventory',
					product_id    : this.productId,
					inventory_data: this.$modal.find( 'form' ).serialize(),
					security      : this.settings.get( 'miListTableNonce' ),
				},
				method  : 'POST',
				dataType: 'json',
				success : ( response: any ) => {

					if ( response.success === false ) {
						Swal.showValidationMessage( response.data );
					}
					else {

						Swal.fire( {
							icon : 'success',
							title: response.data,
						} )
						.then( () => {

							// Update the list table.
							if ( window.hasOwnProperty('atum') && window['atum'].hasOwnProperty( 'ListTable' ) ) {
								const listTable: any = window[ 'atum' ][ 'ListTable' ];
								listTable.updateTable();
							}

						});

					}

					resolve();

				},
			} );

		} );

	}
	
}
