jQuery( function( $ ) {

    /**
     * OpayoPiDropin class.
     */
    var OpayoPiDropin = function( $target ) {

        if( $("input[name='payment_method']:checked").val() === 'opayopi' ) {

            // Params.
            this.params = $.extend( {}, {
                    'merchantSessionKeyArray': false,
                }, opayopi_params );

            var merchantSessionKey = this.params.merchantSessionKeyArray['merchantSessionKey'];

            var checkout = sagepayCheckout({

                merchantSessionKey: merchantSessionKey,
                containerSelector:  '#woocommerce-sp-container',
                onTokenise: function(tokenisationResult) {

                    if (tokenisationResult.success) {

                        // Let's add some hidden fields for Opayo
                        createHiddenInput( document.getElementById( "opayopi-cc-form" ), 'opayopi-cardIdentifier', tokenisationResult.cardIdentifier );
                        createHiddenInput( document.getElementById( "opayopi-cc-form" ), 'opayopi-merchantSessionKey', merchantSessionKey );

                        $( 'form.checkout, form#add_payment_method, form#order_review' ).submit();
                    
                    } else {
                    
                        if (tokenisationResult.error.errorMessage == 'Authentication failed') {
                            alert('Reloading page due to session expiration.');
                            location.reload();
                        } else {
                            alert(tokenisationResult.error.errorMessage);
                        }
                    
                    }

                }
            });

            $('#place_order').on( 'click', function(e) {
                e.preventDefault();
                checkout.tokenise();
            });

        } else {

            // Make sure #woocommerce-sp-container is empty.
            $("#woocommerce-sp-container").empty();

            // Remove Dropin fields
            removeHiddenInput( 'opayopi-cardIdentifier' );
            removeHiddenInput( 'opayopi-merchantSessionKey' );

            // Remove preventDefault from Pay button
            $("#place_order").off('click');
                       
        }

    };

    /**
     * { function_description }
     *
     * @param      {<type>}  args    The arguments
     * @return     {Object}  { description_of_the_return_value }
     */
    $.fn.opayo_pi_dropin = function( args ) {
        new OpayoPiDropin( this, args );
        return this;
    };

    /**
     * Creates a hidden input.
     *
     * @param      {<type>}  form    The form
     * @param      {string}  name    The name
     * @param      {<type>}  value   The value
     */
    function createHiddenInput( form, name, value ) {

        // Check if field exists
        if( $("#"+name).val() !== value ) {

            // Remove the existing field
            $( "#"+name ).remove();

            // Create new field
            var input = document.createElement("input");
            input.setAttribute( "type", "hidden" );
            input.setAttribute( "name", name );
            input.setAttribute( "id", name );
            input.setAttribute( "value", value );
            form.appendChild( input);

        }

    }

    /**
     * Removes a hidden input.
     *
     * @param      {string}  id      The identifier
     */
    function removeHiddenInput( id ) {
        $( "#"+id ).remove();
    }

    /**
     * Initialize the Opayo dropin checkout form.
     */
    $( document.body ).on( 'wc-credit-card-form-init', function() {
        $( this ).opayo_pi_dropin();
    });

    /**
     * Upate the Opayo dropin checkout form.
     */
    $('form.checkout, form#add_payment_method, form#order_review' ).on( 'click', '.payment_methods input.input-radio', function() {
        $( this ).opayo_pi_dropin();
    });

});