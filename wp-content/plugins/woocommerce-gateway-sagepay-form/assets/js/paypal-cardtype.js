( function( $ ) {

	$( function() {

		$( document.body ).on( 'updated_checkout', function() {

			$( '#sagepaydirect-card-type' ).on('change', function() {

	    		if ( 'PayPal' === $( this ).val() ) {
					$( '.not-for-paypal' ).hide();
				} else {
					$( '.not-for-paypal' ).show();
				}

			});

		} );

	});

})( jQuery );