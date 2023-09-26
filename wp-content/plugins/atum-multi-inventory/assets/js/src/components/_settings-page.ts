/* =======================================
   SETTINGS PAGE (MULTI-INVENTORY TAB)
   ======================================= */

import EnhancedSelect from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_enhanced-select';
import Settings from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';

export default class MiSettingsPage {
	
	$settingsWrapper: JQuery;
	$form: JQuery;
	selectFromDefaults: any = {};
	
	constructor(
		private settings: Settings,
		private enhancedSelect: EnhancedSelect
	) {

		this.$settingsWrapper = $( '.atum-settings-wrapper' );
		this.$form = this.$settingsWrapper.find( '#atum-settings' );

		this.$settingsWrapper.on( 'atum-settings-page-loaded', ( evt: any, tab: string ) => {

			if ( 'tools' === tab ) {
				this.initRegionSwitchers();
			}
			// Always enable by default the Geo Prompt switch when choosing the "Shipping Zones" restriction mode.
			else if ( 'multi_inventory' === tab ) {

				$( '#atum_mi_region_restriction_mode' ).on( 'change', ':radio', ( evt: JQueryEventObject ) => {
					const $geoPromptSwitch: JQuery = $( '#atum_mi_use_geoprompt' );

					if ( 'shipping-zones' === $( evt.currentTarget ).val() && ! $geoPromptSwitch.is( ':checked' ) ) {
						$geoPromptSwitch.prop( 'checked', true ).change();
					}
				} );

			}

		} );
		
	}
	
	/**
	 * Initialize the Region Switcher tools
	 */
	initRegionSwitchers() {

		const $regionSwitchers: JQuery = this.$form.find( '.script-runner.region-switcher' );
		
		// Save the initial values to be able to restore them later.
		$regionSwitchers.find( '.select-from' ).each( ( index: number, elem: Element ) => {

			let $select       = $( elem ),
			    defaultValues = {};

			$select.find( 'option' ).each( ( index: number, elem: Element ) => {

				const value: string = $( elem ).val(),
				      label: string = $( elem ).text().trim();

				if ( value ) {
					defaultValues[ value ] = label;
				}

			} );

			this.selectFromDefaults[ $select.attr( 'id' ) ] = defaultValues;

		} );
		
		$regionSwitchers
		
			// Add new row.
			.on( 'click', '.add-row', ( evt: JQueryEventObject ) => {

				const $button: JQuery            = $( evt.currentTarget ),
				      $currentRow: JQuery        = $button.closest( '.repeatable-row' ),
				      $currentSelectFrom: JQuery = $currentRow.find( '.select-from' ),
				      $fieldsWrapper: JQuery     = $button.closest( '.tool-fields-wrapper' );

				if ( $currentSelectFrom.find( 'option' ).length < 2 ) {
					return false; // There are no more options available.
				}

				if ( $currentRow.siblings( '.repeatable-row' ).length + 1 >= Object.keys( this.selectFromDefaults[ $currentSelectFrom.attr( 'id' ) ] ).length ) {
					return false; // There is one row for every available option, so we do not need to add more.
				}

				// Destroy the select2 before cloning
				( <any> $currentRow.find( '.select2-hidden-accessible' ) ).select2( 'destroy' );
				let $newRow = $currentRow.clone();

				// Reuse the ATUM settings' method to restore the select2.
				//self.atumSettingsPlugin.restoreSelects();

				$fieldsWrapper.append( $newRow );

				$newRow.find( 'select' ).val( '' );

				if ( ! $newRow.find( '.remove-row' ).length ) {
					$newRow.find( '.tool-controls' ).append( '<i class="atum-icon atmi-cross-circle remove-row"></i>' );
				}

				// Add the select2 to the cloned row.
				this.enhancedSelect.maybeRestoreEnhancedSelect();

				this.rebuildFromSelects( $fieldsWrapper );

			} )
			
			// Remove row.
			.on( 'click', '.remove-row', ( evt: JQueryEventObject ) => {

				const $currentRow: JQuery    = $( evt.currentTarget ).closest( '.repeatable-row' ),
				      $fieldsWrapper: JQuery = $currentRow.closest( '.tool-fields-wrapper' );

				$currentRow.remove();
				this.rebuildFromSelects( $fieldsWrapper );

			} )
			
			// Bind "Select from" changes.
			.on( 'change', '.select-from', ( evt: JQueryEventObject ) => {

				const $select: JQuery        = $( evt.currentTarget ),
				      selectedOption: string = $select.val(),
				      $fieldsWrapper: JQuery = $select.closest( '.tool-fields-wrapper' );

				if ( selectedOption ) {
					$fieldsWrapper
						.find( '.select-from' ).not( $select )
						.find( 'option' ).filter( '[value="' + selectedOption + '"]' )
						.remove();
				}

				this.rebuildFromSelects( $fieldsWrapper );
				this.updateRegionSwitcherInput( $select.closest( '.region-switcher' ) );

			} )
			
			// Bind "Select to" changes.
			.on( 'change', '.select-to', ( evt: JQueryEventObject ) => this.updateRegionSwitcherInput( $( evt.currentTarget ).closest( '.region-switcher' ) ) )
			
			// Tool runner (button).
			.on( 'click', '.tool-runner', ( evt: JQueryEventObject, params: any ) => {

				// All the fields are valid, just fallback to the default behavior set in ATUM settings.
				if ( typeof params !== 'undefined' && typeof params.force !== 'undefined' && params.force === true ) {
					return;
				}

				evt.stopImmediatePropagation();

				let $button: JQuery        = $( evt.currentTarget ),
				    $fieldsWrapper: JQuery = $button.siblings( '.tool-fields-wrapper' ),
				    isValid: boolean       = true,
				    errorMsg: string       = '';

				// Validate fields
				$fieldsWrapper.find( 'select' ).each( ( index: number, elem: Element ) => {

					// All the fields are required
					if ( ! $( elem ).val() ) {
						isValid = false;
						errorMsg = this.settings.get( 'requiredFields' );
						return false;
					}

				} );

				if ( ! isValid ) {
					$fieldsWrapper.find( '.error-message' ).remove();
					$fieldsWrapper.append( '<em class="error-message">' + errorMsg + '</em>' );

					// Remove the message after 3s
					setTimeout( () => {

						const $errorMessage: any = $fieldsWrapper.find( '.error-message' );
						$errorMessage.fadeOut( () => {
							$errorMessage.remove();
						} );

					}, 3000 );

				}
				else {
					// All valid, re-click the button forcing the ajax call.
					$button.trigger( 'click', { force: true } );
				}

			} );
		
		// Tool callback (reload page).
		this.$settingsWrapper.on( 'atum-settings-script-runner-done', ( evt: any, $scriptRunner: JQuery ) => {

			if ( $scriptRunner.hasClass( 'region-switcher' ) ) {
				location.reload();
			}

		} );
		
	}
	
	/**
	 * Rebuild all the "From Selects" with the available options
	 */
	rebuildFromSelects( $fieldsWrapper: JQuery ) {

		let selectedOptions: string[] = [],
		    $selectFroms: JQuery      = $fieldsWrapper.find( '.select-from' ),
		    id: string                = $selectFroms.first().attr( 'id' );

		$selectFroms.each( ( index: number, elem: Element ) => {

			const value: string = $( elem ).val();

			if ( value ) {
				selectedOptions.push( value );
			}
		} );

		if ( selectedOptions.length ) {

			// Get all the options that are not being used in any select.
			const unselectedOptions: any = $.grep( Object.keys( this.selectFromDefaults[ id ] ), ( elem: any, index: number ) => {
				return $.inArray( elem, selectedOptions ) === -1;
			} );

			$selectFroms.each( ( index: number, elem: Element ) => {

				const $select: JQuery = $( elem ),
				      value: string   = $select.val(),
				      unselected: any = unselectedOptions;

				// Remove from the selects, the options that are being used by other selects within the same container.
				$select.find( 'option' ).each( ( index: number, elem: Element ) => {

					const $option: JQuery     = $( elem ),
					      optionValue: string = $option.val();

					if ( optionValue && optionValue !== value && $.inArray( optionValue, selectedOptions ) > -1 ) {
						$option.remove();
					}

				} );

				// Search for any unselected option that should be readded to the selects.
				if ( unselected.length ) {

					$.each( unselected, ( index: number, elem: any ) => {

						if ( ! $select.find( 'option' ).filter( `[value="${ elem }"]` ).length ) {
							$select.append( `<option value="${ elem }">${ this.selectFromDefaults[ id ][ elem ] }</option>` );
						}

					} );
				}

			} );
		}
		
	}
	
	/**
	 * Update the input hidden of a specific region switcher on every change
	 */
	updateRegionSwitcherInput( $regionSwitcher: JQuery ) {

		let $input: JQuery = $regionSwitcher.find( 'input[type=hidden]' ),
		    values: any[]  = [];

		$regionSwitcher.find( '.repeatable-row' ).each( ( index: number, elem: Element ) => {

			const $row = $( elem ),
			      from = $row.find( '.select-from' ).val(),
			      to   = $row.find( '.select-to' ).val();

			if ( ! from || ! to ) {
				return;
			}

			values.push( {
				from: from,
				to  : to,
			} );

		} );

		$input.val( JSON.stringify( values ) );
		$regionSwitcher.find( '.tool-runner' ).prop( 'disabled', ! values.length );
		
	}
	
}