/* =======================================
   MULTI-INVENTORY GEO PROMPT
   ======================================= */

import Settings from '../../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';
import Cookies from 'js-cookie/src/js.cookie';

export default class MiGeoPrompt {
	
	$geoWrapper: JQuery;
	uiStatus: string = null;
	flInstance: JQueryFeatherlight;
	isGeopromptGpdrChecked: boolean = null;
	geopromptRegionVal: string = null;
	geoPromptPostCodeVal: string = null;
	atumLocationCookieValues: any = {};
	
	constructor(
		private settings: Settings
	) {
		
		// Show the popup.
		this.createUI();
		
		this.$geoWrapper = $('.geo-wrapper');
		
		this.addSelect2();
		
		this.bindEvents();
		
	}
	
	bindEvents() {

		$( 'body' )

			// Validate the form before submit.
			.on( 'submit', '.geo-form', ( evt: JQueryEventObject ) => {
				
				evt.preventDefault();

				const $form: JQuery = $( evt.currentTarget );
				let validate: boolean;
				
				// Get all the values.
				if ( $form.find( '.accept-policy' ).length ) {
					this.isGeopromptGpdrChecked = $form.find( '.accept-policy' ).is( ':checked' );
				}

				this.geopromptRegionVal = $form.find( 'select.region' ).val() || '';
				this.geoPromptPostCodeVal = $form.find( 'input.postcode' ).val() || '';
				
				validate = this.validateForm( $form );

				if ( validate ) {

					this.setAtumLocationCookie();

					if ( this.uiStatus === 'created' ) {
						this.destroyUI();
					}

					$form.find( '.mi-error' ).hide();
					
					// For logged-in users: submit the form and save the data to the user profile.
					if ( typeof this.settings.get( 'loggedIn' ) !== 'undefined' && this.settings.get( 'loggedIn' ) === '1' ) {

						$.ajax( {
							url     : this.settings.get( 'ajaxUrl' ),
							method  : 'POST',
							dataType: 'json',
							data    : {
								action: 'atum_mi_save_user_location',
								token : $form.data( 'nonce' ),
								data  : JSON.stringify( this.atumLocationCookieValues ),
							},
							success : ( response: any ) => {

								if ( typeof response !== 'undefined' && response.success === true ) {
									location.reload(); // The success message will be added as a WC notice after reloading.
								}
								else {

									const $savingError = $( `<div class="mi-error">${ this.settings.get( 'errorSaving' ) }</div>` ).show();
									$form.append( $savingError );

									// Wait for 5 seconds and remove the error.
									setTimeout( () => $savingError.remove(), 5000 );

								}

							},
						} );

					}
					// For non-logged users: reload the page to apply the right MI stocks to the shop products.
					else {

						$form.find( '.mi-success' ).fadeIn( 'fast' );
						setTimeout( () => location.reload(), 500 );
						
					}
					
				}
				else {
					$form.find( '.mi-error' ).fadeIn( 'fast' );
				}
				
			})
			
			// Listen to privacy checkbox changes.
			.on( 'change', '.accept-policy', ( evt: JQueryEventObject ) => {
				const $checkbox: JQuery = $( evt.currentTarget );
				$checkbox.closest( 'form' ).find( 'button' ).prop( 'disabled', ! $checkbox.is( ':checked' ) );
			} );
		
	}
	
	/**
	 * Create the Multi-Inventory GeoPrompt UI and opens for first time.
	 */
	createUI() {

		if ( this.uiStatus === 'created' ) {
			return; // Already created.
		}

		const $template: JQuery = $( '#mi-geo-template' );

		if ( $template.length ) {

			// Get the popup markup from the template and add it to featherlight.
			this.flInstance = $.featherlight( $template.html(), {
				closeOnClick: false,
				closeOnEsc  : false,
			} );

			this.flInstance.open();
			this.uiStatus = 'created';

		}
		
	}
	
	addSelect2() {

		// Add the select2 to the region selector.
		const $regionSelect: JQuery = this.$geoWrapper.find( 'select.region' );
		
		$regionSelect

			.each( ( index: number, elem: Element ) => {

				const $select: any = $( elem );

				$select.select2( {
					dropdownParent: $select.closest( '.geo-wrapper' ),
				} );

				$select.siblings( '.select2-container' ).addClass( 'atum-select2' );

			} )

			// Add custom class to the select2 dropdown on opening.
			.on( 'select2:opening', ( evt: JQueryEventObject ) => {

				const $select: JQuery  = $( evt.currentTarget ),
				      select2Data: any = $select.data();

				if ( select2Data.hasOwnProperty('select2') ) {

					const $dropdown: JQuery = select2Data.select2.dropdown.$dropdown;

					if ( $dropdown.length) {
						$dropdown.addClass('atum-select2-dropdown');
					}
				}

			} );
		
	}
	
	/**
	 * Validate all the required fields.
	 *
	 * @param jQuery $form
	 *
	 * @returns Boolean Are the inputs valid?
	 */
	validateForm($form: JQuery) {
		
		// Privacy policy checkbox required.
		if ( this.isGeopromptGpdrChecked !== null ) {

			if ( ! this.isGeopromptGpdrChecked ) {
				return false;
			}

			this.atumLocationCookieValues[ 'acceptGpdr' ] = this.isGeopromptGpdrChecked;

		}
		
		// Region selector required.
		if ( $form.find( 'select.region' ).length ) {

			if ( this.geopromptRegionVal === null ) {
				return false;
			}

			if (
				this.geopromptRegionVal.length < 2 ||
				( this.geopromptRegionVal.length > 2 && this.geopromptRegionVal.indexOf( ':' ) === -1 )
			) {
				return false;
			}

			// Validate that this country exists on the settings.countries object.
			if ( this.settings.get( 'countries' ).hasOwnProperty( this.geopromptRegionVal.substring( 0, 2 ) ) === false ) {
				return false;
			}

			// It has a state, so validate it too.
			if ( this.geopromptRegionVal.indexOf( ':' ) > -1 && this.settings.get( 'states' )[ this.geopromptRegionVal.substring( 0, 2 ) ].hasOwnProperty( this.geopromptRegionVal.substring( 3 ) ) === false ) {
				return false;
			}

			this.atumLocationCookieValues[ 'region' ] = this.geopromptRegionVal;

		}
		
		// Postal code input required.
		if ( $form.find( 'input.postcode' ).length ) {

			if ( this.geoPromptPostCodeVal === null || this.geoPromptPostCodeVal.length < 4 ) {
				return false;
			}

			this.atumLocationCookieValues[ 'postcode' ] = this.geoPromptPostCodeVal;

		}
		
		return true;
		
	}
	
	/**
	 * Set the Atum Location Cookie with validated form values
	 */
	setAtumLocationCookie() {
		Cookies.set( this.settings.get( 'cookieName' ), JSON.stringify( this.atumLocationCookieValues ), {
			expires: this.settings.get( ' cookieDuration ' ),
			path   : '/',
		} );
	}
	
	/**
	 * Destroy the Multi-Inventory-GeoPrompt UI
	 */
	destroyUI() {
		this.flInstance.close();
		this.uiStatus = null;
	}
	
}