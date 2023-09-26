( function( $ ) {

	$( function() {

		// Set GiftAid section on page load
		$( document.body ).on( 'updated_checkout', function() {

		    if ( 'GB' === $( "#billing_country" ).val() ) {
		    	$( 'p.woocommerce-giftaid' ).show();
		    } else {
		    	$( 'p.woocommerce-giftaid' ).hide();
		    }

		} );

	});

})( jQuery );