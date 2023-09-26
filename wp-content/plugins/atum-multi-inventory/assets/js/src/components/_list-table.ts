/* ==========================================
   MULTI-INVENTORY FOR LIST TABLES COMPONENT
   ========================================== */

import AddInventoryModal from './_add-inventory-modal';
import DateTimePicker from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_date-time-picker';
import Settings from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';
import WPHooks from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/interfaces/wp.hooks';

export default class MiListTable {

	expandableRowClass: string = 'mi-row';
	$listTableWrapper: JQuery;
	wpHooks: WPHooks = window[ 'wp' ][ 'hooks' ]; // WP hooks.

	constructor(
		private settings: Settings,
		private dateTimePicker: DateTimePicker,
	) {

		this.$listTableWrapper = $( '.atum-list-wrapper' );

		this.setRowColors();
		this.bindEvents();
		this.addHooks();

	}

	/**
	 * Bind events
	 */
	bindEvents() {

		this.$listTableWrapper

			// Trigger the expand/collapse event.
			.on( 'click', '.calc_mi_status .has-child', ( evt: JQueryEventObject ) => $( evt.currentTarget ).closest( 'tr' ).trigger( 'atum-list-expand-row', [ this.expandableRowClass, `:not(.${ this.expandableRowClass })` ] ) )

			// Collapse all the inventories of a child product when collapsing the parent.
			.on( 'atum-after-expand-row', ( evt: JQueryEventObject, $row: JQuery, expandableRowClass: string, stopRowSelector: string ) => {

				// Avoid endless loops.
				if ( expandableRowClass === this.expandableRowClass ) {
					return false;
				}

				let $nextChildRow: JQuery = $row.next();

				// As we only need to hide the MI rows when a child product row is collapsed,
				// there is no need to continue if the row is expanded.
				if ( ! $nextChildRow.hasClass( 'collapsing' ) ) {
					return false;
				}

				// Loop until reaching the next main row.
				while ( ! $nextChildRow.filter( stopRowSelector ).length ) {

					if ( ! $nextChildRow.length ) {
						break;
					}

					if ( ! $nextChildRow.hasClass( expandableRowClass ) ) {
						$nextChildRow = $nextChildRow.next();
						continue;
					}

					let $nextInvRow: JQuery = $nextChildRow.next();

					if ( $nextInvRow.hasClass( this.expandableRowClass ) && $nextInvRow.is( ':visible' ) ) {
						// Trigger the MI expand row of the child product row with expanded inventories.
						$nextChildRow.find( 'td.calc_mi_status .has-child' ).click();
					}

					$nextChildRow = $nextChildRow.next();

				}

			} );

	}

	/**
	 * Add hooks
	 */
	addHooks() {

		// Set the row colors after the List Table gets updated.
		this.wpHooks.addAction( 'atum_listTable_tableUpdated', 'atum', () => this.setRowColors() );
		this.wpHooks.addAction( 'atum_stickyHeaders_addedStickyColumns', 'atum', ()  => this.setRowColors() );

		// Block the MI row actions for some items.
		this.wpHooks.addAction( 'atum_menuPopover_inserted', 'atum', ( $popover: JQuery ) => {

			const $row: JQuery = $popover.closest( 'tr' );

			// The default templates cannot be deleted nor downloaded.
			if ( $row.hasClass( 'mi-row' ) || ! $row.find( '.calc_mi_status .multi-inventory' ).length ) {
				$popover.find( 'a[data-name=addInventory]' ).parent().remove();
			}

		} );

		// Bind the MI row actions to methods in this component.
		this.wpHooks.addAction( 'atum_menuPopover_clicked', 'atum', ( evt: JQueryEventObject ) => {

			const $menuItem: JQuery = $( evt.currentTarget ),
			      callback: string  = $menuItem.data( 'name' );

			if ( typeof this[ callback ] === 'function' ) {
				this[ callback ]( $menuItem );
			}

		} );

		// Adjust the title for the locations tree (show) popup.
		this.wpHooks.addFilter(  'atum_LocationsTree_showPopupTitle', 'atum', ( title: string, $button: JQuery ) => {

			if ( !$button.hasClass( 'inventory-locations' ) ) {
				return title;
			}

			return this.settings.get( 'inventoryLocations' );

		} );

		// Adjust the title for the locations tree (edit) popup.
		this.wpHooks.addFilter(  'atum_LocationsTree_editPopupTitle', 'atum', ( title: string, $button: JQuery ) => {

			if ( !$button.hasClass( 'inventory-locations' ) ) {
				return title;
			}

			return this.settings.get( 'editInventoryLocations' );

		} );

	}
	
	/**
	 * Set the even/odd classes for all the MI rows
	 */
	setRowColors() {

		this.$listTableWrapper.find( 'tr.mi-row' ).each( ( index: number, elem: Element ) => {

			const $miRow: JQuery = $( elem ),
			      $first: JQuery = $miRow.prevAll( ':not(.mi-row)' ).first();

			$miRow.css( 'background-color', $first.css( 'background-color' ) );
			//$miRow.css('background-color', $miRow.prevAll(":not(.mi-row)").first().css('background-color'));

		} );
		
	}

	/**
	 * Add a new inventory directly from ATUM list tables
	 *
	 * @param {JQuery} $menuItem
	 */
	addInventory( $menuItem: JQuery ) {

		const $row: JQuery = $menuItem.closest( 'tr' );

		new AddInventoryModal( this.settings, this.dateTimePicker, $row.data( 'id' ), $row.find( '.column-title' ).text().trim() );

	}
	
}