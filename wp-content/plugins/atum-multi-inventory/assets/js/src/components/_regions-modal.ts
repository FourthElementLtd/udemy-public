/* =======================================
   REGIONS MODAL FOR LIST TABLES
   ======================================= */

import EnhancedSelect from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_enhanced-select';
import Settings from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';

export default class RegionsModal {

	$listTableWrapper: JQuery;
	$regionsModal: JQuery;
	$button: JQuery;
	id: number;

	constructor(
		private settings: Settings,
		private enhancedSelect: EnhancedSelect
	) {

		this.$listTableWrapper = $( '.atum-list-wrapper' );

		this.$listTableWrapper.on( 'click', '.show-inventory-regions', ( evt: JQueryEventObject ) => {
			evt.preventDefault();
			this.showRegionsPopup( $( evt.currentTarget ) );
		} );
		
	}
	
	/**
	 * Opens a popup with the regions' dropdown and allows to edit them
	 *
	 * @param {JQuery} $button
	 */
	showRegionsPopup( $button: JQuery ) {

		const $row: JQuery = $button.closest( 'tr' );

		this.$button = $button;
		this.id = $row.data( 'id' );

		// Open the view popup.
		Swal.fire( {
			title              : this.settings.get( 'inventoryRegions' ),
			html               : `<div class="atum-modal-content"><div class="note">${ this.getInventoryTitle( $row ) }</div><hr><div class="inventory-regions"></div></div>`,
			showCancelButton   : false,
			showConfirmButton  : true,
			confirmButtonText  : this.settings.get( 'saveButton' ),
			confirmButtonColor : 'var(--primary)',
			showCloseButton    : true,
			didOpen            : ( popup: HTMLElement ) => this.onOpenViewPopup( popup ),
			background         : 'var(--atum-table-bg)',
			customClass        : {
				container: 'atum-modal',
				popup    : 'regions-modal',
			},
			showLoaderOnConfirm: true,
			preConfirm         : () => this.saveRegions()
		} )
		.then( ( result: SweetAlertResult ) => {

			if ( result.isConfirmed ) {
				Swal.fire( {
					title             : this.settings.get( 'done' ),
					icon              : 'success',
					text              : this.settings.get( 'regionsSaved' ),
					confirmButtonText : this.settings.get( 'ok' ),
					confirmButtonColor: 'var(--primary)',
					background        : 'var(--atum-table-bg)',
				} );
			}

		} );
		
	}
	
	/**
	 * Triggers when the view popup opens
	 *
	 * @param {HTMLElement} popup
	 */
	onOpenViewPopup( popup: HTMLElement ) {

		this.$regionsModal = $( popup );

		const $regionsContainer: JQuery = this.$regionsModal.find( '.inventory-regions' ),
		      $confirmButton: JQuery    = this.$regionsModal.find( '.swal2-confirm ' );

		// Disable the button until a change is done
		$confirmButton.prop( 'disabled', true );

		$.ajax( {
			url       : window[ 'ajaxurl' ],
			dataType  : 'json',
			method    : 'post',
			data      : {
				action: 'atum_get_inventory_regions',
				token : this.settings.get( 'miListTableNonce' ),
				id    : this.id,
			},
			beforeSend: () => $regionsContainer.append( '<div class="atum-loading" />' ),
			success   : ( response: any ) => {

				if ( response.success === true ) {

					$regionsContainer.html( response.data );

					const $regionDropdown: JQuery = $regionsContainer.find( '.edit-inventory-regions' );

					this.enhancedSelect.doSelect2( $regionDropdown, {
						placeholder: {
							id  : '-1',
							text: $regionDropdown.children( 'option' ).first().text(),
						},
					} );
					$regionDropdown.change( () => $confirmButton.prop( 'disabled', false ) );

				}
				else {
					$regionsContainer.html( `<div class="alert alert-danger"><small>${ response.data }</small></div>` );
				}

			},
		} );
		
	}

	/**
	 * Get the inventory title from the table row
	 *
	 * @param {JQuery} $row
	 */
	getInventoryTitle( $row: JQuery ): string {
		const title: string = $row.find( '.column-title' ).find( '.atum-title-small' ).length ? $row.find( '.column-title' ).find( '.atum-title-small' ).text() : $row.find( '.column-title' ).text();

		return title.replace( '↵', '' ).trim();
	}
	
	/**
	 * Saves the selected regions
	 *
	 * @return {Promise<void>}
	 */
	saveRegions(): Promise<void> {

		return new Promise( ( resolve: Function ) => {

			const regions: string[] = this.$regionsModal.find( '.edit-inventory-regions' ).val();

			$.ajax( {
				url     : window[ 'ajaxurl' ],
				dataType: 'json',
				method  : 'post',
				data    : {
					action : 'atum_set_inventory_regions',
					token  : this.settings.get( 'miListTableNonce' ),
					id     : this.id,
					regions: regions,
				},
				success : ( response: any ) => {

					if ( response.success === true ) {

						if ( regions.length ) {
							this.$button.addClass( 'not-empty' );
						}
						else {
							this.$button.removeClass( 'not-empty' );
						}

					}
					else {
						Swal.showValidationMessage( response.data );
					}

					resolve();

				},
			} );

		} );
		
	}
	
}
