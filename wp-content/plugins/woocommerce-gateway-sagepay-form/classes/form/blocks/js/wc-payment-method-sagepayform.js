(function () { 

  // Imports
  const { __ }                            = wp.i18n;
  const { decodeEntities }                = wp.htmlEntities;
  const { getSetting }                    = wc.wcSettings;
  const { registerPaymentMethod }         = wc.wcBlocksRegistry;
  const { registerExpressPaymentMethod }  = wc.wcBlocksRegistry;
  const { applyFilters }                  = wp.hooks;

  // Data
  const settings      = getSetting('sagepayform_data', {});
  const defaultLabel  = OpayoFormLocale['Opayo Form'];
  const label         = decodeEntities(settings.title) || defaultLabel;
  const iconsrc       = settings.iconsrc;
  const poweredbywp   = settings.poweredbywp;

  const iconOutput    = getOpayoIcons( settings.iconsrc );
  

  const Content = () => {
    return decodeEntities( settings.description || '' );
  };

  const Label = props => {
        var label = null;

        if ( poweredbywp != '' ) {
            const icon = React.createElement('img', { alt: __( 'Powered by Opayo', 'woocommerce-gateway-sagepay-form' ), title: __( 'Powered by Opayo', 'woocommerce-gateway-sagepay-form' ), className: 'powered-by-opayo', src:poweredbywp});
            label = icon;
        } else {
          const { PaymentMethodLabel } = props.components;
          label = React.createElement( PaymentMethodLabel, { text: label, icon: icon } );
        }

        return applyFilters( 'wc_checkout_label_sagepayform', label, settings );
  };

  function getOpayoIcons( iconsrc ){

    return Object.entries( iconsrc ).map(
      ( [ id, { src, alt } ] ) => {
        return {
          id,
          src,
          alt,
        };
      }
    );

  }

  function getIconOutput( index ) {
    return output;
  }

  const IconHTML = () => {
    return React.createElement('img', { alt: __( 'Powered by Opayo', 'woocommerce-gateway-sagepay-form' ), title: __( 'Powered by Opayo', 'woocommerce-gateway-sagepay-form' ), className: 'powered-by-opayo', src:poweredbywp});
  };
  
  const OpayoFormPaymentMethod = {
        name: 'sagepayform',
        label: React.createElement( Label, null ),
        content: React.createElement( Content, null ),
        edit: React.createElement( Content, null ),
        placeOrderButtonLabel: OpayoFormLocale['Proceed to Opayo'],
        canMakePayment: () => true,
        ariaLabel: label
  };

  // Register Opayo Form
  registerPaymentMethod( OpayoFormPaymentMethod );

}());