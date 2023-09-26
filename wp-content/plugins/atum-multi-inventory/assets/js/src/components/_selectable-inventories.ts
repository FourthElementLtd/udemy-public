/* =======================================
 MULTI-INVENTORY SELECTABLE INVENTORIES
 ======================================= */

export default class SelectableInventories {

	$selectableList: JQuery;
	$selectableDropdown: JQuery;
	$cart: JQuery;
	$variationsForm: JQuery;

	constructor() {

		// Only needed for product pages.
		if ( $( 'body' ).hasClass( 'single-product' ) ) {

			// Selectable inventories in product pages (dropdown mode).
			this.$selectableDropdown = $ ( '.atum-select-mi' );

			if ( this.$selectableDropdown.length ) {
				this.doSelectableDropDownUI();
			}

		}

		// Selectable inventories in product pages (list mode).
		this.$selectableList = $( '.atum-select-mi-list' );

		if ( this.$selectableList.length ) {
			this.doSelectableListUI();
		}

		// Selectable inventories in cart.
		this.$cart = $( '.woocommerce-cart-form' );

		if ( this.$cart.length ) {
			this.doSelectableCartItems();
		}

		// Selectable inventories for variations.
		this.$variationsForm = $( '.variations_form' );

		if ( this.$variationsForm.length ) {
			this.doSelectableVariations();
		}

	}

	/**
	 * Prepare the selectable inventories UI (dropdown mode)
	 */
	doSelectableDropDownUI() {

		const $cartForm: JQuery        = this.$selectableDropdown.closest( 'form' ),
		      $productQtyInput: JQuery = $cartForm.find( '[name="quantity"]' );

		this.$selectableDropdown.on( 'change', ( evt: JQueryEventObject ) => {

			// Adapt the max for the quantity input dynamically.
			const $dropdown: JQuery = $( evt.currentTarget ),
			      max: number       = parseFloat( $dropdown.find( 'option:selected' ).data( 'max' ) );

			if ( max < 0 )
				$productQtyInput.attr( 'max', '' );
			else {
				$productQtyInput.attr( 'max', max );

				if ( parseFloat( $productQtyInput.val() ) > max ) {
					$productQtyInput.val( max );
				}
			}

		} ).change(); // Force the change.

	}

	/**
	 * Prepare the selectable inventories UI (list mode)
	 */
	doSelectableListUI() {

		const $cartForm: JQuery        = this.$selectableList.closest( 'form' ),
		      $productQtyInput: JQuery = $cartForm.find( '[name="quantity"]' ).prop( 'readonly', true ),
		      $invQtyInput: JQuery     = $cartForm.find( '[name^="atum[select-mi]"]' ),
		      $multiPriceWrapper: JQuery = $cartForm.find( '.atum-select-mi-list__multi-price' );

		$invQtyInput.change( () => {

			let qty: number      = 0,
			    prices: string[] = [];

			$invQtyInput.each( ( index: number, elem: Element ) => {

				const $elem: JQuery      = $( elem ),
				      $item: JQuery      = $elem.closest( '.atum-select-mi-list__item' ),
				      currentQty: number = parseFloat( $elem.val() );

				qty += currentQty;

				if ( currentQty > 0 && $item.find( '.woocommerce-Price-amount' ).length ) {
					prices.push( `${ currentQty } x ${ $item.find( '.woocommerce-Price-amount' ).text().trim() }` );
				}

			} );

			$productQtyInput.val( qty );

			if ( $multiPriceWrapper.length ) {
				$multiPriceWrapper.text( prices.join( ' + ' ) );
				$cartForm.find( ':submit').prop( 'disabled', ! prices.length );
			}

		} );

		// As we are adding 1 to the first inventory by default, trigger the change to get the price on the multi-price wrapper.
		if ( $multiPriceWrapper.length ) {
			$invQtyInput.first().change();
		}

	}

	/**
	 * Prepare the selectable inventory items in cart
	 */
	doSelectableCartItems() {

		$( 'body' ).on( 'change', '.woocommerce-cart-form .atum-mi-qty', ( evt: JQueryEventObject ) => {

			const $invQtyInput: JQuery = $( evt.currentTarget );

			let itemQty: number = parseFloat( $invQtyInput.val() ),
			    $invRow: JQuery = $invQtyInput.closest( '.atum-mi-cart-item' ),
			    $currentRow: JQuery = $invRow.prev(),
				$productRow: JQuery;

			// Run the rows backwards.
			while ( $currentRow.length && $currentRow.hasClass( 'atum-mi-cart-item' ) ) {
				itemQty += parseFloat( $currentRow.find( '.atum-mi-qty' ).val() );
				$currentRow = $currentRow.prev();
			}

			// Once we reach the product line at top, store it in a variable.
			$productRow = $currentRow;

			// Run the rows onwards.
			$currentRow = $invRow.next();

			while ( $currentRow.length && $currentRow.hasClass( 'atum-mi-cart-item' ) ) {
				itemQty += parseFloat( $currentRow.find( '.atum-mi-qty' ).val() );
				$currentRow = $currentRow.next();
			}

			if ( $productRow.length ) {
				$productRow.find( '.quantity :input' ).val( itemQty );
			}

		} );

	}

	/**
	 * Prepare the selectable inventories for variations
	 */
	doSelectableVariations() {

		const $singleVariation: JQuery = this.$variationsForm.find( '.woocommerce-variation.single_variation' );

		// Add the selectable MI UI to the variation once is shown.
		$singleVariation.on( 'show_variation', ( evt: JQueryEventObject, variation: any, purchasable: boolean ) => {

			// Reset the readonly attribute in case it was set by another variation.
			this.$variationsForm.find( '[name="quantity"]' ).removeAttr( 'readonly' );

			if ( variation && variation.hasOwnProperty( 'atum_mi_selectable' ) ) {

				$singleVariation.append( variation.atum_mi_selectable );

				// Bind the selectable list UI (if enabled).
				this.$selectableList = $( '.atum-select-mi-list' );

				if ( this.$selectableList.length ) {
					this.doSelectableListUI();
				}

				// Only needed for product pages.
				if ( $( 'body' ).hasClass( 'single-product' ) ) {

					// Selectable inventories in product pages (dropdown mode).
					this.$selectableDropdown = $ ( '.atum-select-mi' );

					if ( this.$selectableDropdown.length ) {
						this.doSelectableDropDownUI();
					}

				}

			}

		} );

	}

}